<?php

namespace Tests\Unit\Traits;

use Deagel1337\Backup\Kit\Process\Interface\ProcessRunner;
use Deagel1337\Backup\Kit\Process\Model\ProcessResult;
use Deagel1337\Backup\Kit\Traits\CommandTrait;
use PHPUnit\Framework\TestCase;

final class CommandTraitTest extends TestCase
{
    public function test_returns_true_when_command_succeeds(): void
    {
        $runner = $this->createMock(ProcessRunner::class);

        $runner
            ->expects($this->once())
            ->method('run')
            ->with([
                'php',
                '--version',
            ])
            ->willReturn(
                new ProcessResult(
                    0,
                    'PHP 8.4.0',
                    ''
                )
            );

        $testClass = new class($runner)
        {
            use CommandTrait;

            public function __construct(
                private ProcessRunner $runner
            ) {}

            protected function processRunner(): ProcessRunner
            {
                return $this->runner;
            }
        };

        $this->assertTrue(
            $testClass->isCommandAvailable('php')
        );
    }

    public function test_returns_false_when_command_fails(): void
    {
        $runner = $this->createMock(ProcessRunner::class);

        $runner
            ->expects($this->once())
            ->method('run')
            ->with([
                'invalid-command',
                '--version',
            ])
            ->willReturn(
                new ProcessResult(
                    1,
                    '',
                    'Command not found'
                )
            );

        $testClass = new class($runner)
        {
            use CommandTrait;

            public function __construct(
                private ProcessRunner $runner
            ) {}

            protected function processRunner(): ProcessRunner
            {
                return $this->runner;
            }
        };

        $this->assertFalse(
            $testClass->isCommandAvailable('invalid-command')
        );
    }
}
