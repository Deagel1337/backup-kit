<?php

namespace Tests\Unit\Reporter\Laravel;

use Deagel1337\Backup\Kit\Reporter\Laravel\EventProgressReporter;
use Deagel1337\Backup\Kit\Reporter\Laravel\Events\ProgressRunFinished;
use Deagel1337\Backup\Kit\Reporter\Laravel\Events\ProgressRunStarted;
use Deagel1337\Backup\Kit\Reporter\Laravel\Events\ProgressStepFailed;
use Deagel1337\Backup\Kit\Reporter\Laravel\Events\ProgressStepFinished;
use Deagel1337\Backup\Kit\Reporter\Laravel\Events\ProgressStepStarted;
use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use PHPUnit\Framework\TestCase;

final class EventProgressReporterTest extends TestCase
{
    public function test_dispatches_typed_progress_events(): void
    {
        $events = [];
        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher->expects($this->exactly(5))
            ->method('dispatch')
            ->with($this->callback(static function (object $event) use (&$events): bool {
                $events[] = $event;

                return true;
            }));

        $reporter = new EventProgressReporter($dispatcher);
        $reporter->started(2);
        $reporter->stepStarted(1, 2, 'Backup');
        $reporter->stepFinished(1, 2, 'Backup');
        $reporter->stepFailed(2, 2, 'Restore', new Exception('Sensitive connection details'));
        $reporter->finished();

        self::assertSame([
            ProgressRunStarted::class,
            ProgressStepStarted::class,
            ProgressStepFinished::class,
            ProgressStepFailed::class,
            ProgressRunFinished::class,
        ], array_map(static fn (object $event): string => $event::class, $events));
        self::assertSame(2, $events[0]->totalSteps);
        self::assertSame(['step' => 1, 'totalSteps' => 2, 'name' => 'Backup'], [
            'step' => $events[1]->step,
            'totalSteps' => $events[1]->totalSteps,
            'name' => $events[1]->name,
        ]);
        self::assertSame(Exception::class, $events[3]->exceptionClass);
        self::assertObjectNotHasProperty('exception', $events[3]);
    }
}
