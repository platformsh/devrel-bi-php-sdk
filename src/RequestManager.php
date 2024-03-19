<?php

namespace Platformsh\DevRelBIPhpSdk;

use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class RequestManager{

    public function __construct(
        private HttpClientInterface $client
    ) {}

    public function batch(string $projectName, array $processedEventDataList): ?ResponseInterface
    {
        try {
            return $this->client->request(
                'POST',
                $_ENV['DEVREL_DATA_PIPELINE_ENDPOINT'] . '/data/' . $projectName . '/batch',
                [
                    'timeout' => 1.0,
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                    'auth_bearer' => $_ENV['DEVREL_DATA_PIPELINE_TOKEN'],
                    'json' => $processedEventDataList,
                ]
            );
        } catch (TransportExceptionInterface $e) {
            // ...
        }

        return null;
    }
}
