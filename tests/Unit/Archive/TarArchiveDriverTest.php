<?php

namespace Tests\Archive\Driver;

use Archive\Driver\TarArchiveDriver\TarArchiveDriver;
use Archive\Model\ArchiveInfo;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class TarArchiveDriverTest extends TestCase
{
    private string $archivePath;

    protected function setUp(): void
    {
        $this->archivePath = tempnam(sys_get_temp_dir(), 'tar_test_');
        file_put_contents($this->archivePath, 'archive');
    }

    protected function tearDown(): void
    {
        unlink($this->archivePath);
    }

    public function testAcceptsExistingTarArchive(): void
    {
        (new TarArchiveDriver())->validateArchive(new ArchiveInfo($this->archivePath, 'tar', 'tar.gz'));

        $this->addToAssertionCount(1);
    }

    public function testRejectsArchiveForAnotherDriver(): void
    {
        $this->expectExceptionMessage('Kein Tar-Archiv.');

        (new TarArchiveDriver())->validateArchive(new ArchiveInfo($this->archivePath, 'borg', 'borg'));
    }

    public function testRejectsMissingArchive(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Die Archiv-Datei existiert nicht.');

        (new TarArchiveDriver())->validateArchive(new ArchiveInfo('/tmp/missing-tar-archive', 'tar', 'tar.gz'));
    }
}