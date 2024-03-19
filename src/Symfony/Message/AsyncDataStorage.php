<?php

namespace Platformsh\DevRelBIPhpSdk\Symfony\Message;

class AsyncDataStorage
{
    public function __construct(
        private string $projectName,
        private array $processedEventDataList,
    ) {
    }

    public function getProjectprojectName(): string
    {
        return $this->projectName;
    }

    public function getProcessedEventDataList(): array
    {
        return $this->processedEventDataList;
    }
}
