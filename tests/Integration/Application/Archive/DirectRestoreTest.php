<?php

namespace Tests\Integration\Application\Archive;

use Deagel1337\Backup\Kit\Archive\Driver\TarArchiveDriver;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Deagel1337\Backup\Kit\Context\RestoreContext;
use Deagel1337\Backup\Kit\Process\Runner\ProcOpenProcessRunner;
use Deagel1337\Backup\Kit\Reporter\Interface\ProgressReporter;
use Deagel1337\Backup\Kit\Services\RestoreService;
use Deagel1337\Backup\Kit\Step\Restore\RestoreArchiveStep;
use Deagel1337\Backup\Kit\Step\Restore\RestoreDirectoryCleaner;
use Deagel1337\Backup\Kit\Step\Runner\StepRunner;
use PHPUnit\Framework\TestCase;

final class DirectRestoreTest extends TestCase
{
    public function test_direct_restore_overwrites_matching_files_and_preserves_others(): void
    {
        $root = sys_get_temp_dir().'/direct_restore_'.bin2hex(random_bytes(6));
        mkdir($root, 0700);

        try {
            mkdir($root.'/source', 0700);
            mkdir($root.'/destination', 0700);
            file_put_contents($root.'/source/file.txt', 'new');
            file_put_contents($root.'/destination/file.txt', 'old');
            file_put_contents($root.'/destination/other.txt', 'keep');
            $process = new ProcOpenProcessRunner;
            $result = $process->run(['tar', '-czf', $root.'/backup.tar.gz', '-C', $root.'/source', 'file.txt']);
            $this->assertSame(0, $result->exitCode, $result->errorOutput);

            $service = new RestoreService(
                [new RestoreArchiveStep(new TarArchiveDriver($process))],
                new StepRunner($this->createStub(ProgressReporter::class)),
            );
            $service->restore(new RestoreContext(
                new ArchiveInfo($root.'/backup.tar.gz', 'tar', 'tar.gz'),
                null,
                $root.'/destination',
            ));

            $this->assertSame('new', file_get_contents($root.'/destination/file.txt'));
            $this->assertSame('keep', file_get_contents($root.'/destination/other.txt'));
        } finally {
            RestoreDirectoryCleaner::remove($root);
        }
    }
}
