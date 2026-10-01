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
    public function testRejectsEmptyRepository(): void
    {
        $this->expectExceptionMessage('Es wurde kein Borg-Repository angegeben.');

        new BorgArchiveDriver('   ');
    }

    public function testAcceptsBorgArchiveWithoutCheckingLocalFile(): void
    {
        (new BorgArchiveDriver('/var/lib/borg'))->validateArchive(new ArchiveInfo('repo::backup', 'borg', 'borg'));

        $this->addToAssertionCount(1);
    }

    public function testRejectsArchiveForAnotherDriver(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Kein Borg-Archiv.');

        (new BorgArchiveDriver('/var/lib/borg'))->validateArchive(new ArchiveInfo('repo::backup', 'tar', 'tar.gz'));
    }

    public function testCreatesBorgArchive(): void
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

    public function testCreateArchiveThrowsWhenBorgFails(): void
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

    public function testCreateArchiveRejectsNonExistingPath(): void
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

    public function testExtractArchiveRunsBorgExtract(): void
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

    public function testUsesSshPort(): void
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

    public function testListsBorgArchiveContent(): void
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

    public function testListsBorgArchiveWithRepositoryAndArchiveNamePath(): void
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

    public function testListAllBorgArchives(): void
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