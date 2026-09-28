<?php

namespace Backup\Php\Context;

final class DockerContainerContext
{
    public function __construct(
        public string $volumeName, 
        public string $containerName, 
        public ?string $dataPath, 
        public string $destinationPath
    ) {}
}