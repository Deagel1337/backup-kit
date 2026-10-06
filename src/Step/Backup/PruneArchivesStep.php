<?php

namespace Deagel1337\Backup\Kit\Step\Backup;

use Deagel1337\Backup\Kit\Archive\Model\RetentionPolicy;
use Deagel1337\Backup\Kit\Context\BackupContext;
use Deagel1337\Backup\Kit\Services\ArchiveService;
use Deagel1337\Backup\Kit\Step\Interface\BackupStep;

/**
 * Entfernt Archive, die von den Aufbewahrungsregeln nicht mehr abgedeckt werden.
 */
final class PruneArchivesStep implements BackupStep
{
    public function __construct(
        private readonly ArchiveService $archives,
        private readonly RetentionPolicy $policy,
    ) {}

    public function name(): string
    {
        return 'Alte Archive aufräumen';
    }

    public function execute(BackupContext $context): void
    {
        $this->archives->prune(...$this->policy->toArray());
    }
}
