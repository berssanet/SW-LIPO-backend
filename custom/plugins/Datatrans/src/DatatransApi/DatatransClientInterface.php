<?php declare(strict_types=1);

namespace Allquanto\Datatrans\DatatransApi;

interface DatatransClientInterface
{
    public function sendPost(string $resourceUri, array $data, array $headers = []): array;

    public function sendGet(string $resourceUri, array $headers = []): array;

    public function sendPatch(string $resourceUri, array $data, array $headers = []): array;

    public function sendPut(string $resourceUri, array $data, array $headers = []): array;

    public function sendDelete(string $resourceUri, array $headers = []): array;
}
