<?php

namespace Platformsh\DevRelBIPhpSdk\Symfony\MessageHandler;

use Platformsh\DevRelBIPhpSdk\RequestManager;
use Platformsh\DevRelBIPhpSdk\Symfony\Message\AsyncDataStorage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class AsyncDataStorageHandler
{
    public function __construct(
        private RequestManager $requestManager
    ) {}

    public function __invoke(AsyncDataStorage $message)
    {
        $this->requestManager->batch(
            $message->getProjectprojectName(),
            $message->getProcessedEventDataList()
        );
    }
}