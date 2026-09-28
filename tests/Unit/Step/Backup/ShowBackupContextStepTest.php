<?php

namespace Tests\Unit\Step\Backup;

use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Deagel1337\Backup\Kit\Context\BackupContext;
use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseDump;
use Deagel1337\Backup\Kit\Step\Backup\ShowBackupContextStep;
use PHPUnit\Framework\TestCase;

final class ShowBackupContextStepTest extends TestCase
{
    private ShowBackupContextStep $step;

    protected function setUp(): void
    {
        $this->step = new ShowBackupContextStep();
    }

    public function testName(): void
    {
        $this->assertSame(
            'Backup-Kontext anzeigen',
            $this->step->name()
        );
    }

    public function testOutputsEmptyContext(): void
    {
        $context = new BackupContext('/backup');

        $this->expectOutputString(
            "=== Backup Context ===\n"
            . "Destination: /backup\n"
            . "Dump: keiner\n"
            . "Archive: keines\n"
            . "=======================\n"
        );

        $this->step->execute($context);
    }

    public function testOutputsContextWithDump(): void
    {
        $dump = new DatabaseDump(
            '/tmp/backup.sql',
            'mysql',
            'sql'
        );

        $context = new BackupContext(
            destination: '/backup',
            dump: $dump
        );

        $this->expectOutputString(
            "=== Backup Context ===\n"
            . "Destination: /backup\n"
            . "Dump:\n"
            . "  Path: /tmp/backup.sql\n"
            . "  Driver: mysql\n"
            . "  Format: sql\n"
            . "Archive: keines\n"
            . "=======================\n"
        );

        $this->step->execute($context);
    }

    public function testOutputsContextWithArchive(): void
    {
        $archive = new ArchiveInfo(
            '/backup/archive.zip',
            'zip',
            'zip'
        );

        $context = new BackupContext(
            destination: '/backup',
            archive: $archive
        );

        $this->expectOutputString(
            "=== Backup Context ===\n"
            . "Destination: /backup\n"
            . "Dump: keiner\n"
            . "Archive:\n"
            . "  Path: /backup/archive.zip\n"
            . "  Driver: zip\n"
            . "  Format: zip\n"
            . "=======================\n"
        );

        $this->step->execute($context);
    }

    public function testOutputsCompleteContext(): void
    {
        $dump = new DatabaseDump(
            '/tmp/backup.sql',
            'mysql',
            'sql'
        );

        $archive = new ArchiveInfo(
            '/backup/archive.zip',
            'zip',
            'zip'
        );

        $context = new BackupContext(
            destination: '/backup',
            dump: $dump,
            archive: $archive
        );

        $this->expectOutputString(
            "=== Backup Context ===\n"
            . "Destination: /backup\n"
            . "Dump:\n"
            . "  Path: /tmp/backup.sql\n"
            . "  Driver: mysql\n"
            . "  Format: sql\n"
            . "Archive:\n"
            . "  Path: /backup/archive.zip\n"
            . "  Driver: zip\n"
            . "  Format: zip\n"
            . "=======================\n"
        );

        $this->step->execute($context);
    }
}