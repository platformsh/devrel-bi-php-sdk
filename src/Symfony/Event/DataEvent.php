<?php

namespace Platformsh\DevRelBIPhpSdk\Symfony\Event;

use Platformsh\DevRelBIPhpSdk\EventData;
use Symfony\Contracts\EventDispatcher\Event;

class DataEvent extends Event
{
    public function __construct(
        private string $eventName,
        private array $data = []
    ) {
    }

    public function getEventData(): EventData
    {
        return new EventData($this->eventName, $this->data);
    }
}