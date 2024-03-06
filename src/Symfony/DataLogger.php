<?php

namespace Platformsh\DevRelBIPhpSdk\Symfony;

use Platformsh\DevRelBIPhpSdk\Symfony\Event\DataEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

class DataLogger
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher
    ) {}

    public function log(string $eventName, array $data = []): void
    {
        $this->eventDispatcher->dispatch(new DataEvent($eventName, $data));
    }
}