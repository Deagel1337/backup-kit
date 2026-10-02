<?php

namespace Tests\Unit\Reporter;

use Deagel1337\Backup\Kit\Reporter\ConsoleProgressReporter;
use Exception;
use PHPUnit\Framework\TestCase;

final class ConsoleProgressReporterTest extends TestCase
{
    private ConsoleProgressReporter $reporter;

    protected function setUp(): void
    {
        $this->reporter = new ConsoleProgressReporter;
    }

    public function test_started_outputs_correct_message(): void
    {
        $this->expectOutputString(
            "Backup gestartet(3 Schritte)\n"
        );

        $this->reporter->started(3);
    }

    public function test_step_started_outputs_correct_message(): void
    {
        $this->expectOutputString(
            '[2/5] Dateien kopieren ...'
        );

        $this->reporter->stepStarted(
            2,
            5,
            'Dateien kopieren'
        );
    }

    public function test_step_finished_outputs_ok(): void
    {
        $this->expectOutputString(
            "OK\n"
        );

        $this->reporter->stepFinished(
            2,
            5,
            'Dateien kopieren'
        );
    }

    public function test_step_failed_outputs_error_message(): void
    {
        $exception = new Exception(
            'Datei konnte nicht kopiert werden.'
        );

        $this->expectOutputString(
            "[2/5] Schritt Dateien kopieren Fehlgeschlagen:\n"
            .'Datei konnte nicht kopiert werden.'
        );

        $this->reporter->stepFailed(
            2,
            5,
            'Dateien kopieren',
            $exception
        );
    }

    public function test_finished_outputs_correct_message(): void
    {
        $this->expectOutputString(
            "Backup abgeschlossen.\n"
        );

        $this->reporter->finished();
    }
}
