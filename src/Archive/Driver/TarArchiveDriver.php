<?php

namespace Deagel1337\Backup\Kit\Archive\Driver;

use Deagel1337\Backup\Kit\Archive\Interfaces\ArchiveDriver;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveEntry;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveEntryType;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Deagel1337\Backup\Kit\Process\Interface\ProcessRunner;
use Deagel1337\Backup\Kit\Process\Runner\ProcOpenProcessRunner;
use Deagel1337\Backup\Kit\Traits\CommandTrait;
use InvalidArgumentException;
use Override;
use RuntimeException;

final class TarArchiveDriver implements ArchiveDriver
{
    use CommandTrait;

    private readonly string $archiveDirectory;

    public function __construct(
        private readonly ProcessRunner $process = new ProcOpenProcessRunner,
        ?string $archiveDirectory = null,
    ) {
        $this->archiveDirectory = $archiveDirectory
            ?? sys_get_temp_dir().DIRECTORY_SEPARATOR.'backup-kit-tar';
    }

    protected function processRunner(): ProcessRunner
    {
        return $this->process;
    }

    public function createArchive(array $paths, string $archiveName): ArchiveInfo
    {
        $name = basename($archiveName);
        if ($name === '' || $name === '.' || $name === '..') {
            throw new InvalidArgumentException('Der Archivname darf nicht leer sein.');
        }

        $this->ensureArchiveDirectoryExists();

        $path = $this->archiveDirectory.DIRECTORY_SEPARATOR.$name;

        $command = array_merge(['tar', '-czf', $path], $paths);

        $result = $this->process->run($command);

        if ($result->exitCode !== 0) {
            throw new RuntimeException(
                'Das Tar-Archiv konnte nicht erstellt werden: '.trim($result->errorOutput)
            );
        }

        return new ArchiveInfo($path, 'tar', 'tar.gz');
    }

    public function validateArchive(ArchiveInfo $archive): void
    {
        if ($archive->driver !== 'tar') {
            throw new RuntimeException('Kein Tar-Archiv.');
        }

        if (! $archive->exists()) {
            throw new RuntimeException('Die Archiv-Datei existiert nicht.');
        }
    }

    public function extractArchive(
        ArchiveInfo $archive,
        string $destination,
        array $paths = [],
        int $stripComponents = 0,
    ): void {
        $this->validateArchive($archive);

        $command = ['tar', '-xzf', $archive->path, '-C', $destination];

        if ($stripComponents > 0) {
            $command[] = '--strip-components='.$stripComponents;
        }

        foreach ($paths as $path) {
            $command[] = ltrim($path, '/');
        }

        $result = $this->process->run($command);

        if ($result->exitCode !== 0) {
            throw new RuntimeException(
                'Das Tar-Archiv konnte nicht entpackt werden: '.trim($result->errorOutput)
            );
        }
    }

    public function listContent(ArchiveInfo $archive): string
    {
        $this->validateArchive($archive);

        $command = ['tar', '-ztvf', $archive->path];

        $result = $this->process->run($command);

        if ($result->exitCode !== 0) {
            throw new RuntimeException(
                'Das Tar-Archiv konnte nicht gezeigt werden.'
            );
        }

        return $result->output;
    }

    #[Override]
    public function listArchives(): iterable
    {
        if (is_link($this->archiveDirectory)) {
            throw new RuntimeException('Das Archivverzeichnis darf kein Symlink sein: '.$this->archiveDirectory);
        }

        if (! is_dir($this->archiveDirectory)) {
            if (file_exists($this->archiveDirectory)) {
                throw new RuntimeException('Das Archivverzeichnis ist kein Verzeichnis: '.$this->archiveDirectory);
            }

            return [];
        }

        $entries = scandir($this->archiveDirectory);
        if ($entries === false) {
            throw new RuntimeException('Das Archivverzeichnis konnte nicht gelesen werden: '.$this->archiveDirectory);
        }

        $archives = [];
        foreach ($entries as $entry) {
            $path = $this->archiveDirectory.DIRECTORY_SEPARATOR.$entry;
            if ($entry === '.' || $entry === '..' || ! is_file($path) || is_link($path)) {
                continue;
            }

            $modifiedAt = filemtime($path);
            if ($modifiedAt === false) {
                throw new RuntimeException('Das Änderungsdatum des Archivs konnte nicht gelesen werden: '.$path);
            }

            $archives[] = [
                'modifiedAt' => $modifiedAt,
                'archive' => new ArchiveInfo($path, 'tar', 'tar.gz'),
            ];
        }

        usort(
            $archives,
            static fn (array $left, array $right): int => $right['modifiedAt'] <=> $left['modifiedAt']
                ?: strcmp($right['archive']->path, $left['archive']->path)
        );

        return array_column($archives, 'archive');
    }

    #[Override]
    public function listArchive(ArchiveInfo $archive): iterable
    {
        $this->validateArchive($archive);

        $result = $this->process->run(['tar', '-tvzf', $archive->path]);
        if ($result->exitCode !== 0) {
            throw new RuntimeException(
                'Der Inhalt des Tar-Archivs konnte nicht gelesen werden: '.trim($result->errorOutput)
            );
        }

        foreach (explode("\n", trim($result->output)) as $line) {
            if ($line === '') {
                continue;
            }

            if (! preg_match('/^([\\-dlcbps])\\S*\\s+\\S+\\s+(\\d+)\\s+\\S+\\s+\\S+\\s(.+)$/', $line, $matches)) {
                throw new RuntimeException('Ein Eintrag des Tar-Archivs konnte nicht gelesen werden: '.$line);
            }

            $type = match ($matches[1]) {
                '-' => ArchiveEntryType::File,
                'd' => ArchiveEntryType::Directory,
                'l' => ArchiveEntryType::Symlink,
                default => ArchiveEntryType::Undefined,
            };

            yield new ArchiveEntry($matches[3], (int) $matches[2], $type);
        }
    }

    /**
     * Behält Archive gemäß den übergebenen Aufbewahrungsregeln und entfernt alle anderen.
     *
     * Die Archive werden nach Änderungszeit der Datei sortiert. Kalenderzeiträume werden in der
     * lokalen Zeitzone ausgewertet, wobei der aktuelle Zeitraum als erster zählt.
     *
     * Die Regeln werden kombiniert: Ein Archiv bleibt erhalten, wenn mindestens eine Regel es auswählt.
     * Mindestens eine Regel muss angegeben werden. Der Wert 0 behält für die jeweilige Regel nichts.
     *
     * @param  int|null  $keepLast  Behält die N neuesten Archive.
     * @param  int|null  $keepDaily  Behält das neueste Archiv der N jüngsten Tage.
     * @param  int|null  $keepWeekly  Behält das neueste Archiv der N jüngsten Wochen.
     * @param  int|null  $keepMonthly  Behält das neueste Archiv der N jüngsten Monate.
     * @param  int|null  $keepYearly  Behält das neueste Archiv der N jüngsten Jahre.
     *
     * @throws InvalidArgumentException Wenn keine Regel angegeben wurde oder ein Wert negativ ist.
     * @throws RuntimeException Wenn Archive nicht aufgelistet oder entfernt werden können.
     */
    #[Override]
    public function prune(
        ?int $keepLast = null,
        ?int $keepDaily = null,
        ?int $keepWeekly = null,
        ?int $keepMonthly = null,
        ?int $keepYearly = null,
    ): void {
        $retentionRules = [
            'daily' => $keepDaily,
            'weekly' => $keepWeekly,
            'monthly' => $keepMonthly,
            'yearly' => $keepYearly,
        ];
        if ($keepLast === null && ! array_filter($retentionRules, static fn (?int $count): bool => $count !== null)) {
            throw new InvalidArgumentException('Es muss mindestens eine Aufbewahrungsregel angegeben werden.');
        }

        foreach ([$keepLast, ...array_values($retentionRules)] as $count) {
            if ($count !== null && $count < 0) {
                throw new InvalidArgumentException('Die Anzahl der zu behaltenden Archive darf nicht negativ sein.');
            }
        }

        $archives = $this->listArchives();
        $keptPaths = [];
        if ($keepLast !== null) {
            foreach (array_slice($archives, 0, $keepLast) as $archive) {
                $keptPaths[$archive->path] = true;
            }
        }

        $now = new \DateTimeImmutable;
        foreach ($retentionRules as $period => $count) {
            if ($count === null || $count === 0) {
                continue;
            }

            $currentPeriod = $this->periodStart($now, $period);
            $keptPeriods = [];
            foreach ($archives as $archive) {
                $modifiedAt = filemtime($archive->path);
                if ($modifiedAt === false) {
                    throw new RuntimeException('Das Änderungsdatum des Archivs konnte nicht gelesen werden: '.$archive->path);
                }

                $date = (new \DateTimeImmutable('@'.$modifiedAt))->setTimezone($now->getTimezone());
                $archivePeriod = $this->periodStart($date, $period);
                $age = $this->periodAge($currentPeriod, $archivePeriod, $period);
                if ($age >= 0 && $age < $count && ! isset($keptPeriods[$archivePeriod->format('Y-m-d')])) {
                    $keptPaths[$archive->path] = true;
                    $keptPeriods[$archivePeriod->format('Y-m-d')] = true;
                }
            }
        }

        foreach ($archives as $archive) {
            if (isset($keptPaths[$archive->path])) {
                continue;
            }

            if (! unlink($archive->path)) {
                throw new RuntimeException('Das Archiv konnte nicht entfernt werden: '.$archive->path);
            }
        }
    }

    public function validateRequirements(): void
    {
        if (! $this->isCommandAvailable('tar')) {
            throw new RuntimeException('Das Programm tar ist nicht verfügbar.');
        }
    }

    /**
     * Erstellt bei Bedarf das Archivverzeichnis und lehnt Symlinks ab.
     *
     * @throws RuntimeException Wenn das Verzeichnis nicht erstellt werden kann oder ein Symlink ist.
     */
    private function ensureArchiveDirectoryExists(): void
    {
        if (is_link($this->archiveDirectory)) {
            throw new RuntimeException('Das Archivverzeichnis darf kein Symlink sein: '.$this->archiveDirectory);
        }

        if (! is_dir($this->archiveDirectory)
            && ! mkdir($this->archiveDirectory, 0700, true)
            && ! is_dir($this->archiveDirectory)) {
            throw new RuntimeException('Das Archivverzeichnis konnte nicht erstellt werden: '.$this->archiveDirectory);
        }

        if (is_link($this->archiveDirectory)) {
            throw new RuntimeException('Das Archivverzeichnis darf kein Symlink sein: '.$this->archiveDirectory);
        }
    }

    /**
     * Gibt den Beginn des Kalenderzeitraums (Tag, ISO-Woche, Monat oder Jahr) zurück, der das Datum enthält.
     *
     * @param  'daily'|'weekly'|'monthly'|'yearly'  $period
     */
    private function periodStart(\DateTimeImmutable $date, string $period): \DateTimeImmutable
    {
        return match ($period) {
            'daily' => $date->setTime(0, 0),
            'weekly' => $date->modify('monday this week')->setTime(0, 0),
            'monthly' => $date->modify('first day of this month')->setTime(0, 0),
            'yearly' => $date->setDate((int) $date->format('Y'), 1, 1)->setTime(0, 0),
        };
    }

    /**
     * Gibt zurück, wie viele Zeiträume der Archivzeitraum vor dem aktuellen liegt (0 = aktuell).
     *
     * @param  'daily'|'weekly'|'monthly'|'yearly'  $period
     */
    private function periodAge(
        \DateTimeImmutable $currentPeriod,
        \DateTimeImmutable $archivePeriod,
        string $period,
    ): int {
        return match ($period) {
            'daily' => (int) $archivePeriod->diff($currentPeriod)->format('%r%a'),
            'weekly' => (int) floor((int) $archivePeriod->diff($currentPeriod)->format('%r%a') / 7),
            'monthly' => ((int) $currentPeriod->format('Y') * 12 + (int) $currentPeriod->format('n'))
                - ((int) $archivePeriod->format('Y') * 12 + (int) $archivePeriod->format('n')),
            'yearly' => (int) $currentPeriod->format('Y') - (int) $archivePeriod->format('Y'),
        };
    }
}
