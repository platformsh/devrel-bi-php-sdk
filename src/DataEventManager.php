<?php

namespace Platformsh\DevRelBIPhpSdk;

use DateTime;
use DateTimeInterface;
use Platformsh\DevRelBIPhpSdk\EventData;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class DataEventManager
{
    private string $projectName;
    private RequestManager $requestManager;

    public function __construct(
        private HttpClientInterface $client,
        private ?string $userId,
        private array $eventDataList = [],
        private array $sharedData = []
    ) {
        $this->projectName = strtolower($_ENV['DEVREL_DATA_PIPELINE_PROJECT' ?? '']);
        if (empty($this->projectName)) {
            return;
        }

        $this->requestManager = new RequestManager($client);

        if ($this->shouldLogPageviews() && !$this->isProfiling()) {
            $this->track(EventData::new('pageview'));
        }

        if ($this->shouldLogProfiles() && $this->isProfiling()) {
            $this->track(EventData::new('profile'));
        }
    }

    public function track(EventData $dataEvent): void
    {
        $this->eventDataList[] = $dataEvent;
    }

    public function setSharedData(array $sharedData): void
    {
        $this->sharedData = $sharedData;
    }

    public function sync(?callable $func = null): void
    {
        if (!$this->checkConfiguration()) {
            return;
        }

        if (is_callable($func)) {
            $func($this->projectName, $this->processEventDataList());
            return;
        }

        $this->postRequest($this->processEventDataList());
    }

    private function postRequest(array $processedEventDataList): void
    {
        $this->requestManager->batch($this->projectName,  $processedEventDataList);
    }

    private function processEventDataList(): array
    {
        return array_map(
            function (EventData $eventData): array {
                $data = [
                    'project' => $this->projectName,
                    'requestUri' => $_SERVER['REQUEST_URI'],
                    ...$this->getUTMTags(),
                    ...$this->sharedData,
                    ...$eventData->getData(),
                ];
                if ($this->isProfiling()) {
                    $data['is_profiling'] = array_key_exists('HTTP_X_BLACKFIRE_QUERY', $_SERVER) ? 'true' : 'false';
                }

                return [
                    'event' => $this->projectName . ':' . $eventData->getEventName(),
                    'datetime' => (new DateTime('now'))->format(DateTimeInterface::ATOM),
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                    'ip_address' => $this->getClientIp(),
                    'user_id' => $this->userId,
                    'data' => $data,
                ];
            },
            $this->eventDataList
        );
    }

    private function checkConfiguration(): bool
    {
        if (!$this->projectName) {
            return false;
        }

        $pipelineEndpoint = $_ENV['DEVREL_DATA_PIPELINE_ENDPOINT'] ?? null;
        if (!$pipelineEndpoint) {
            return false;
        }

        $pipelineToken = $_ENV['DEVREL_DATA_PIPELINE_TOKEN'] ?? null;
        if (!$pipelineToken) {
            return false;
        }

        return true;
    }

    private function shouldLogPageviews(): bool
    {
        return filter_var($_ENV['DEVREL_DATA_PIPELINE_LOG_PAGEVIEWS'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    private function shouldLogProfiles(): bool
    {
        return filter_var($_ENV['DEVREL_DATA_PIPELINE_LOG_PROFILES'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    private function isProfiling(): bool
    {
        return array_key_exists('HTTP_X_BLACKFIRE_QUERY', $_SERVER);
    }

    private function getClientIp(): string
    {
        $keyList = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];
        foreach ($keyList as $key) {
            if (array_key_exists($key, $_SERVER)) {
                return $_SERVER[$key];
            }
        }

        return '';
    }

    private function getUTMTags(): array
    {
        if (!array_key_exists('QUERY_STRING', $_SERVER)) {
            return [];
        }

        return array_reduce(
            explode('&', $_SERVER['QUERY_STRING']),
            function (array $list, string $string): array {
                $element = explode('=', $string);
                $key = strtolower($element[0]);
                if (in_array($key, ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'])) {
                    $list[$key] = $element[1] ?? '';
                }
                return $list;
            },
            []
        );
    }
}