<?php

namespace Tests\Unit\Archive\Driver;

use Deagel1337\Backup\Kit\Archive\Driver\BorgArchiveDriver;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Deagel1337\Backup\Kit\Process\Interface\ProcessRunner;
use Deagel1337\Backup\Kit\Process\Model\ProcessResult;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class BorgArchiveDriverTest extends TestCase
{
    public function test_rejects_empty_repository(): void
    {
        $this->expectExceptionMessage('Es wurde kein Borg-Repository angegeben.');

        new BorgArchiveDriver('   ');
    }

    public function test_accepts_borg_archive_without_checking_local_file(): void
    {
        (new BorgArchiveDriver('/var/lib/borg'))->validateArchive(new ArchiveInfo('repo::backup', 'borg', 'borg'));

        $this->addToAssertionCount(1);
    }

    public function test_rejects_archive_for_another_driver(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Kein Borg-Archiv.');

        (new BorgArchiveDriver('/var/lib/borg'))->validateArchive(new ArchiveInfo('repo::backup', 'tar', 'tar.gz'));
    }

    public function test_creates_borg_archive(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'borg-test-');

        try {
            $process = $this->createMock(ProcessRunner::class);

            $process
                ->expects($this->once())
                ->method('run')
                ->with(
                    [
                        'borg',
                        'create',
                        '--stats',
                        '/var/lib/borg::backup',
                        $path,
                    ],
                    ['BORG_PASSPHRASE' => 'secret']
                )
                ->willReturn(
                    new ProcessResult(
                        exitCode: 0,
                        output: '',
                        errorOutput: '',
                    )
                );

            $driver = new BorgArchiveDriver(
                repository: '/var/lib/borg',
                passphrase: 'secret',
                process: $process,
            );

            $archive = $driver->createArchive(
                [$path],
                'backup'
            );

            $this->assertSame(
                '/var/lib/borg::backup',
                $archive->path
            );
        } finally {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    public function test_create_archive_throws_when_borg_fails(): void
    {
        $process = $this->createMock(ProcessRunner::class);

        $process
            ->method('run')
            ->willReturn(
                new ProcessResult(
                    exitCode: 1,
                    output: '',
                    errorOutput: 'Repository not found',
                )
            );

        $driver = new BorgArchiveDriver(
            repository: '/var/lib/borg',
            process: $process,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            '<error>Invalider Pfad entdeckt: /tmp/test1.txt</error>'
        );

        $driver->createArchive(
            ['/tmp/test1.txt'],
            'backup'
        );
    }

    public function test_create_archive_rejects_non_existing_path(): void
    {
        $process = $this->createMock(ProcessRunner::class);

        $process
            ->expects($this->never())
            ->method('run');

        $driver = new BorgArchiveDriver(
            repository: '/var/lib/borg',
            process: $process,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalider Pfad entdeckt');

        $driver->createArchive(
            ['/does/not/exist'],
            'backup'
        );
    }

    public function test_extract_archive_runs_borg_extract(): void
    {
        $process = $this->createMock(ProcessRunner::class);

        $process
            ->expects($this->once())
            ->method('run')
            ->with(
                [
                    'borg',
                    'extract',
                    '/var/lib/borg::backup',
                ],
                ['BORG_PASSPHRASE' => 'secret'],
                '/tmp/extract'
            )
            ->willReturn(
                new ProcessResult(
                    exitCode: 0,
                    output: '',
                    errorOutput: '',
                )
            );

        $driver = new BorgArchiveDriver(
            repository: '/var/lib/borg',
            passphrase: 'secret',
            process: $process,
        );

        $driver->extractArchive(
            new ArchiveInfo(
                '/var/lib/borg::backup',
                'borg',
                'borg'
            ),
            '/tmp/extract'
        );
    }

    public function test_uses_ssh_port(): void
    {
        $process = $this->createMock(ProcessRunner::class);

        $process
            ->expects($this->once())
            ->method('run')
            ->with(
                [
                    'borg',
                    'list',
                    '--format',
                    '{archive}{NL}',
                    '--rsh',
                    "ssh -p '2222'",
                    '/var/lib/borg',
                ],
                ['BORG_PASSPHRASE' => '']
            )
            ->willReturn(new ProcessResult(
                exitCode: 0,
                output: '',
                errorOutput: ''
            ));

        $driver = new BorgArchiveDriver(
            repository: '/var/lib/borg',
            sshPort: 2222,
            process: $process,
        );

        iterator_to_array($driver->listArchives());
    }

    public function test_lists_borg_archive_content(): void
    {
        $process = $this->createMock(ProcessRunner::class);

        $process
            ->expects($this->once())
            ->method('run')
            ->with(
                [
                    'borg',
                    'list',
                    '--rsh',
                    "ssh -p '2222'",
                    '--format',
                    '{path}{TAB}{size}{TAB}{type}{NL}',
                    '/var/lib/borg::testArchive',
                ],
                ['BORG_PASSPHRASE' => '']
            )
            ->willReturn(
                new ProcessResult(
                    exitCode: 0,
                    output: '',
                    errorOutput: ''
                )
            );

        $driver = new BorgArchiveDriver(
            repository: '/var/lib/borg',
            sshPort: 2222,
            process: $process,
        );

        $archive = new ArchiveInfo(
            path: 'testArchive',
            driver: 'borg',
            format: 'borg'
        );

        iterator_to_array($driver->listArchive($archive));
    }

    public function test_lists_borg_archive_with_repository_and_archive_name_path(): void
    {
        $process = $this->createMock(ProcessRunner::class);

        $process
            ->expects($this->once())
            ->method('run')
            ->with(
                [
                    'borg',
                    'list',
                    '--rsh',
                    "ssh -p '2222'",
                    '--format',
                    '{path}{TAB}{size}{TAB}{type}{NL}',
                    '/var/lib/borg::testArchive',
                ],
                ['BORG_PASSPHRASE' => '']
            )
            ->willReturn(
                new ProcessResult(
                    exitCode: 0,
                    output: '',
                    errorOutput: ''
                )
            );

        $driver = new BorgArchiveDriver(
            repository: '/var/lib/borg',
            sshPort: 2222,
            process: $process,
        );

        $archive = new ArchiveInfo(
            path: '/var/lib/borg::testArchive',
            driver: 'borg',
            format: 'borg'
        );

        iterator_to_array($driver->listArchive($archive));
    }

    public function test_list_all_borg_archives(): void
    {
        $process = $this->createMock(ProcessRunner::class);

        $process
            ->expects($this->once())
            ->method('run')
            ->with(
                [
                    'borg',
                    'list',
                    '--format',
                    '{archive}{NL}',
                    '--rsh',
                    "ssh -p '2222'",
                    '/var/lib/borg',
                ]
            )
            ->willReturn(
                new ProcessResult(
                    exitCode: 0,
                    output: '',
                    errorOutput: ''
                )
            );

        $driver = new BorgArchiveDriver(
            repository: '/var/lib/borg',
            sshPort: 2222,
            process: $process
        );

        iterator_to_array($driver->listArchives());
    }
}
