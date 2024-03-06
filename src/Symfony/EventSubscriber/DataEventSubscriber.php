<?php

namespace Platformsh\DevRelBIPhpSdk\Symfony\EventSubscriber;

use Platformsh\DevRelBIPhpSdk\EventData;
use Platformsh\DevRelBIPhpSdk\DataEventManager;
use Platformsh\DevRelBIPhpSdk\Symfony\Event\DataEvent;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class DataEventSubscriber implements EventSubscriberInterface
{
    protected DataEventManager $dataEventManager;

    public function __construct(
        private HttpClientInterface $client,
        protected Security $security,
        protected array $eventDataList = [],
        protected ?string $userId = null
    ) {
        $this->dataEventManager = new DataEventManager(
            client: $this->client,
            userId: $this->userId,
            eventDataList: $this->eventDataList,
        );
    }
    abstract protected function logEvents(RequestEvent $event): void;

    abstract protected function getUserId(RequestEvent $event): ?string;

    abstract protected function getSharedData(RequestEvent $event): array;

    public function log(string $eventName, ?array $data = []): void
    {
        $this->dataEventManager->track(EventData::new($eventName, $data));
    }

    public function onRequestEvent(RequestEvent $event): void
    {
        $this->logEvents($event);
        $this->userId = $this->getUserId($event);
        $this->dataEventManager->setSharedData($this->getSharedData($event));
    }

    public function onDataEvent(DataEvent $dataEvent): void
    {
        $this->dataEventManager->track($dataEvent->getEventData());
    }

    public function onFinishRequestEvent(FinishRequestEvent $event): void
    {
        $this->dataEventManager->sync();
    }

    public static function getSubscribedEvents()
    {
        return [
            RequestEvent::class => 'onRequestEvent',
            FinishRequestEvent::class => 'onFinishRequestEvent',
            DataEvent::class => 'onDataEvent',
        ];
    }
}
