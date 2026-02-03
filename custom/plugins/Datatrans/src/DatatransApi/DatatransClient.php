<?php declare(strict_types=1);

namespace Allquanto\Datatrans\DatatransApi;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class DatatransClient extends AbstractClient implements DatatransClientInterface
{

    public function __construct(LoggerInterface $logger, string $baseUrl, array $basicAuth)
    {
        $client = new Client([
            'base_uri' => $baseUrl,
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'auth' => $basicAuth
        ]);
        parent::__construct($client, $logger);
    }

    public function sendPost(string $resourceUri, array $data, array $headers = []): array
    {
        $options = [
            'headers' => $headers,
            'json' => $data,
        ];

        return $this->post($resourceUri, $options);
    }

    public function sendGet(string $resourceUri, array $headers = []): array
    {
        $options = [
            'headers' => $headers,
        ];

        return $this->get($resourceUri, $options);
    }

    public function sendPatch(string $resourceUri, array $data, array $headers = []): array
    {
        $options = [
            'headers' => $headers,
            'json' => $data,
        ];

        return $this->patch($resourceUri, $options);
    }

    public function sendPut(string $resourceUri, array $data, array $headers = []): array
    {
        $options = [
            'headers' => $headers,
            'json' => $data,
        ];

        return $this->put($resourceUri, $options);
    }

    public function sendDelete(string $resourceUri, array $headers = []): array
    {
        $options = [
            'headers' => $headers,
        ];

        return $this->delete($resourceUri, $options);
    }
}
