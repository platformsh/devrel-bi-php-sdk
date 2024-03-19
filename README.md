# DevRel Data Pipeline PHP SDK

This is a PHP integration with the DevRel Data Pipeline. It mains at logging user engagements for the onboarding and discovery applications.

It logs:
- `pageview` for regular visit
- `profile` for profiled page

The `logEvents` method below could be used to log specific action.

## Environment variables

The following environments variables are required to activate the integration:
 - `DEVREL_DATA_PIPELINE_PROJECT`
 - `DEVREL_DATA_PIPELINE_ENDPOINT`
 - `DEVREL_DATA_PIPELINE_TOKEN`
 - `DEVREL_DATA_PIPELINE_LOG_PAGEVIEWS`
 - `DEVREL_DATA_PIPELINE_LOG_PROFILES`

## Symfony

**1/ Create an `EventSubscriber`**

`src/EventSubscriber/DataSubscriber.php`

 ```
<?php

namespace App\EventSubscriber;

use Platformsh\DevRelBIPhpSdk\Symfony\EventSubscriber\DataEventSubscriber;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class DataSubscriber extends DataEventSubscriber
{
    protected function logEvents(RequestEvent $event): void
    {
        // $this->log('foo-bar', ['optional' => 'data']);
    }

    protected function getUserId(RequestEvent $event): ?string
    {
        return null;
    }

    // Extra data that will be added to all tracking events
    protected function getSharedData(RequestEvent $event): array
    {
        return [
            'foo' => 'bar',
        ];
    }
 ```

**2/ Enable autowiring**

`config/services.yaml`

```
services:
    ...

    Platformsh\DevRelBIPhpSdk\Symfony\DataLogger:
        autowire:

    Platformsh\DevRelBIPhpSdk\RequestManager:
        autowire: true

    Platformsh\DevRelBIPhpSdk\Symfony\MessageHandler\AsyncDataStorageHandler:
        autowire: true
```

**3/ Custom tracking from controller**

```
use Platformsh\DevRelBIPhpSdk\Symfony\DataLogger;

public function landing(
    ...
    DataLogger $dataLogger
): Response {
    ...
    $dataLogger->log('landing-page-form-submitted');
    ..
}
```

**4/ Enable async**

```
composer require symfony/messenger symfony/redis-messenger
```

Provide a callback that will dispath the message instead of synchronously syncing them

```
<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Platformsh\DevRelBIPhpSdk\Symfony\EventSubscriber\DataEventSubscriber;
use Platformsh\DevRelBIPhpSdk\Symfony\Message\AsyncDataStorage;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DataSubscriber extends DataEventSubscriber
{
    public function __construct(
        private HttpClientInterface $client,
        protected Security $security,
        private MessageBusInterface $bus,
        protected array $eventDataList = [],
        protected ?string $userId = null
    ) {
        parent::__construct(
            client: $client,
            security: $security,
            eventDataList: $eventDataList,
            userId: $userId
        );
    }


    protected function sync(?callable $func = null): void
    {
        $func = function (string $projectName, array $processedEventDataList): void {
            $this->bus->dispatch(new AsyncDataStorage($projectName, $processedEventDataList));
        };

        $this->dataEventManager->sync($func);
    }

```
