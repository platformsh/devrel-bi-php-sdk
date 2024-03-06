<?php

namespace Platformsh\DevRelBIPhpSdk;

class EventData
{
    public function __construct(
        private string $eventName,
        private array $data = []
    ) {}

    public static function new(string $eventName, array $data = []): self
    {
        return new self($eventName, $data);
    }

    public function getEventName(): string
    {
        return $this->eventName;
    }

    public function getData(): array
    {
        return $this->data;
    }
}