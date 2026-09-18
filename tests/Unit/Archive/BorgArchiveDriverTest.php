<?php

namespace Tests\Unit\Archive;

use Backup\Php\Archive\Driver\BorgArchiveDriver;
use Backup\Php\Archive\Model\ArchiveInfo;
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
}