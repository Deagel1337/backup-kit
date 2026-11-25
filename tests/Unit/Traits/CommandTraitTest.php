<?php

namespace Tests\Unit\Traits;

use Deagel1337\Backup\Kit\Process\Interface\ProcessRunner;
use Deagel1337\Backup\Kit\Process\Model\ProcessResult;
use Deagel1337\Backup\Kit\Traits\CommandTrait;
use Override;
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

            protected function allowedCommands(): array
            {
                return ['php'];
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

            protected function allowedCommands(): array
            {
                return ['invalid-command'];
            }
        };

        $this->assertFalse(
            $testClass->isCommandAvailable('invalid-command')
        );
    }

    public function test_returns_true_when_command_available(): void
    {
        $runner = $this->createMock(ProcessRunner::class);

        $runner
            ->expects($this->never())
            ->method('run')
            ->with(['valid-command'])
            ->willReturn(
                new ProcessResult(0,'true','')
            );

        $testClass = new class($runner)
        {
            use CommandTrait;

            public function __construct(private ProcessRunner $runner)
            { }

            protected function processRunner(): ProcessRunner
            {
                return $this->runner;
            }

            protected function allowedCommands(): array
            {
                return ['valid-command'];
            }
        };

        $this->assertTrue(
            $testClass->isCommandAllowed('valid-command')
        );
    }

    public function test_returns_false_when_command_unavailable(): void
    {
        $command = 'invliad-command';
        $runner = $this->createMock(ProcessRunner::class);

        $runner
            ->expects($this->never())
            ->method('run')
            ->with([$command])
            ->willReturn(
                new ProcessResult(0,'true','')
            );

        $testClass = new class($runner)
        {
            use CommandTrait;

            public function __construct(private ProcessRunner $runner)
            { }

            protected function processRunner(): ProcessRunner
            {
                return $this->runner;
            }

            protected function allowedCommands(): array
            {
                return [];
            }
        };

        $this->assertFalse($testClass->isCommandAllowed('invalid-command'));
    }

    public function test_returns_true_when_both_commands_are_available(): void
    {
        $commands = ['valid-command', 'second-valid-command'];
        $runner = $this->createMock(ProcessRunner::class);

        $runner
            ->expects($this->never())
            ->method('run')
            ->with($commands)
            ->willReturn(
                new ProcessResult(0,'true','')
            );

        $testClass = new class($runner)
        {
            use CommandTrait;

            public function __construct(private ProcessRunner $runner)
            { }


            protected function processRunner(): ProcessRunner
            {
                return $this->runner;
            }

            protected function allowedCommands(): array
            {
                return ['valid-command', 'second-valid-command'];
            }
        };

        $this->assertTrue($testClass->isCommandAllowed('valid-command'));
        $this->assertTrue($testClass->isCommandAllowed('second-valid-command'));
    }

    public function test_returns_false_when_command_is_empty_or_invalid_string(): void
    {
        $commands = ['valid-command'];
        $runner = $this->createMock(ProcessRunner::class);

        $runner
            ->expects($this->never())
            ->method('run')
            ->with($commands)
            ->willReturn(
                new ProcessResult(0,'true','')
            );

        $testClass = new class($runner)
        {
            use CommandTrait;

            public function __construct(private ProcessRunner $runner)
            { }


            protected function processRunner(): ProcessRunner
            {
                return $this->runner;
            }

            protected function allowedCommands(): array
            {
                return ['valid-command', 'second-valid-command'];
            }
        };

        $this->assertFalse($testClass->isCommandAllowed(''));
        $this->assertFalse($testClass->isCommandAllowed(' '));
        $this->assertFalse($testClass->isCommandAllowed('valid-command --help'));
        $this->assertFalse($testClass->isCommandAllowed('/tmp/valid-command'));
        $this->assertFalse($testClass->isCommandAllowed('valid-command-extra-bits'));
    }

    public function test_available_check_never_runs_disallowed_command(): void
    {
        $runner = $this->createMock(ProcessRunner::class);
        $runner->expects($this->never())->method('run');

        $testClass = new class($runner) {
            use CommandTrait;

            public function __construct(private ProcessRunner $runner) {}

            protected function processRunner(): ProcessRunner
            {
                return $this->runner;
            }

            protected function allowedCommands(): array
            {
                return ['valid-command'];
            }
        };

        $this->assertFalse(
            $testClass->isCommandAvailable('invalid-command')
        );
    }
}
