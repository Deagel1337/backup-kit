<?php

namespace Src\Services;

use Restore\Model\RestoreContext;

final class RestoreService
{
    public function __construct(
        private readonly array $steps,
    ) {}

    public function restore(RestoreContext $context): void
    {
        foreach($this->steps as $step) {
            $step->execute($context);
        }
    }
}