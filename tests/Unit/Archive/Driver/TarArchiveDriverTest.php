<?php

namespace Tests\Unit\Archive\Driver;

use Deagel1337\Backup\Kit\Archive\Driver\TarArchiveDriver;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveEntryType;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Deagel1337\Backup\Kit\Process\Interface\ProcessRunner;
use Deagel1337\Backup\Kit\Process\Model\ProcessResult;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class TarArchiveDriverTest extends TestCase
{
    private string $archivePath;

    private string $archiveDirectory;

    protected function setUp(): void
    {
        $this->archivePath = tempnam(sys_get_temp_dir(), 'tar_test_');
        file_put_contents($this->archivePath, 'archive');

        $this->archiveDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'tar_driver_test_'.bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        unlink($this->archivePath);
        if (is_dir($this->archiveDirectory)) {
            foreach (scandir($this->archiveDirectory) ?: [] as $entry) {
                if ($entry !== '.' && $entry !== '..') {
                    unlink($this->archiveDirectory.DIRECTORY_SEPARATOR.$entry);
                }
            }
            rmdir($this->archiveDirectory);
        }

        parent::tearDown();
    }

    public function test_accepts_existing_tar_archive(): void
    {
        (new TarArchiveDriver)->validateArchive(new ArchiveInfo($this->archivePath, 'tar', 'tar.gz'));

        $this->addToAssertionCount(1);
    }

    public function test_rejects_archive_for_another_driver(): void
    {
        $this->expectExceptionMessage('Kein Tar-Archiv.');

        (new TarArchiveDriver)->validateArchive(new ArchiveInfo($this->archivePath, 'borg', 'borg'));
    }

    public function test_rejects_missing_archive(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Die Archiv-Datei existiert nicht.');

        (new TarArchiveDriver)->validateArchive(new ArchiveInfo('/tmp/missing-tar-archive', 'tar', 'tar.gz'));
    }

    public function test_lists_tar_archive_entries(): void
    {
        $process = $this->createMock(ProcessRunner::class);
        $process
            ->expects($this->once())
            ->method('run')
            ->with(['tar', '-tvzf', $this->archivePath])
            ->willReturn(new ProcessResult(
                0,
                "-rw-r--r-- user/group 4 2026-10-06 09:50 dump.sql\ndrwxr-xr-x user/group 0 2026-10-06 09:50 app/\n",
                ''
            ));

        $entries = iterator_to_array((new TarArchiveDriver($process))->listArchive(
            new ArchiveInfo($this->archivePath, 'tar', 'tar.gz')
        ));

        $this->assertSame('dump.sql', $entries[0]->path);
        $this->assertSame(4, $entries[0]->size);
        $this->assertSame(ArchiveEntryType::File, $entries[0]->type);
        $this->assertSame(ArchiveEntryType::Directory, $entries[1]->type);
    }

    public function test_creates_tar_archive_in_dedicated_directory(): void
    {
        $process = $this->createMock(ProcessRunner::class);
        $process
            ->expects($this->once())
            ->method('run')
            ->with(['tar', '-czf', $this->archiveDirectory.'/backup.tar.gz', '/tmp/source'])
            ->willReturn(new ProcessResult(0, '', ''));

        $archive = (new TarArchiveDriver($process, $this->archiveDirectory))
            ->createArchive(['/tmp/source'], 'backup.tar.gz');

        $this->assertSame($this->archiveDirectory.'/backup.tar.gz', $archive->path);
        $this->assertSame('tar', $archive->driver);
    }

    public function test_lists_archives_newest_first_and_prunes_older_archives(): void
    {
        mkdir($this->archiveDirectory);
        $oldest = $this->archiveDirectory.'/oldest.tar.gz';
        $middle = $this->archiveDirectory.'/middle.tar.gz';
        $newest = $this->archiveDirectory.'/newest.tar.gz';
        $unrelated = dirname($this->archiveDirectory).'/tar_driver_unrelated_'.bin2hex(random_bytes(6));
        foreach ([$oldest, $middle, $newest, $unrelated] as $path) {
            file_put_contents($path, 'archive');
        }
        touch($oldest, 100);
        touch($middle, 200);
        touch($newest, 300);

        try {
            $driver = new TarArchiveDriver(archiveDirectory: $this->archiveDirectory);
            $archives = iterator_to_array($driver->listArchives());

            $this->assertSame([$newest, $middle, $oldest], array_map(
                static fn (ArchiveInfo $archive): string => $archive->path,
                $archives
            ));

            $driver->prune(2);

            $this->assertFileExists($newest);
            $this->assertFileExists($middle);
            $this->assertFileDoesNotExist($oldest);
            $this->assertFileExists($unrelated);
        } finally {
            if (file_exists($unrelated)) {
                unlink($unrelated);
            }
        }
    }

    public function test_prune_with_zero_removes_all_tar_archives(): void
    {
        mkdir($this->archiveDirectory);
        file_put_contents($this->archiveDirectory.'/backup.tar.gz', 'archive');

        (new TarArchiveDriver(archiveDirectory: $this->archiveDirectory))->prune(0);

        $this->assertSame([], iterator_to_array(
            (new TarArchiveDriver(archiveDirectory: $this->archiveDirectory))->listArchives()
        ));
    }

    public function test_prune_rejects_negative_retention_count(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new TarArchiveDriver(archiveDirectory: $this->archiveDirectory))->prune(-1);
    }

    public function test_prune_applies_each_calendar_retention_rule(): void
    {
        mkdir($this->archiveDirectory);

        $today = new \DateTimeImmutable('today');
        $thisMonth = $today->modify('first day of this month');
        $thisYear = $today->setDate((int) $today->format('Y'), 1, 1);
        $cases = [
            'keepDaily' => [
                'dates' => [
                    'current' => $today,
                    'previous' => $today->modify('-1 day'),
                    'old' => $today->modify('-2 days'),
                ],
            ],
            'keepWeekly' => [
                'dates' => [
                    'current' => $today->modify('monday this week'),
                    'previous' => $today->modify('monday this week')->modify('-1 week'),
                    'old' => $today->modify('monday this week')->modify('-2 weeks'),
                ],
            ],
            'keepMonthly' => [
                'dates' => [
                    'current' => $thisMonth,
                    'previous' => $thisMonth->modify('-1 day'),
                    'old' => $thisMonth->modify('-1 month')->modify('-1 day'),
                ],
            ],
            'keepYearly' => [
                'dates' => [
                    'current' => $thisYear,
                    'previous' => $thisYear->modify('-1 day'),
                    'old' => $thisYear->modify('-1 year')->modify('-1 day'),
                ],
            ],
        ];

        foreach ($cases as $rule => $case) {
            foreach ($case['dates'] as $name => $date) {
                $path = $this->archiveDirectory.'/'.$name.'.tar.gz';
                file_put_contents($path, 'archive');
                touch($path, $date->getTimestamp());
            }

            (new TarArchiveDriver(archiveDirectory: $this->archiveDirectory))->prune(...[$rule => 2]);

            $this->assertFileExists($this->archiveDirectory.'/current.tar.gz', $rule);
            $this->assertFileExists($this->archiveDirectory.'/previous.tar.gz', $rule);
            $this->assertFileDoesNotExist($this->archiveDirectory.'/old.tar.gz', $rule);

            unlink($this->archiveDirectory.'/current.tar.gz');
            unlink($this->archiveDirectory.'/previous.tar.gz');
        }
    }

    public function test_prune_requires_at_least_one_retention_rule(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new TarArchiveDriver(archiveDirectory: $this->archiveDirectory))->prune();
    }
}
