<?php

namespace Level51\DataDocuments;

use Exception;
use GuzzleHttp\Client;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injectable;

class PocketbaseAdapter implements DataDocumentStore
{
    use Injectable;

    private Client $httpClient;

    private string $collection;

    public function __construct(string $collection)
    {
        if (!Environment::getEnv('POCKETBASE_URL')
            || !Environment::getEnv('POCKETBASE_ADMIN_USER')
            || !Environment::getEnv('POCKETBASE_ADMIN_PASS')) {
            throw new Exception('missing pocketbase configuration');
        }

        $this->collection = $collection;

        $clientConfig = [
            'base_uri' => Environment::getEnv('POCKETBASE_URL') . '/api/',
            'headers'  => [
                'Content-Type' => 'application/json',
            ],
        ];

        $this->httpClient = new Client($clientConfig);

        if ($adminToken = $this->getAdminToken()) {
            $clientConfig['headers']['Authorization'] = $adminToken;

            $this->httpClient = new Client($clientConfig);
        }
    }

    private function getAdminToken()
    {
        $response = $this->httpClient->post(
            'collections/_superusers/auth-with-password',
            [
                'json' => [
                    'identity' => Environment::getEnv('POCKETBASE_ADMIN_USER'),
                    'password' => Environment::getEnv('POCKETBASE_ADMIN_PASS'),
                ],
            ],
        );

        if ($content = $response->getBody()->getContents()) {
            return json_decode($content, true)['token'] ?? null;
        }

        return null;
    }

    private function createRecord($recordId, $payload): void
    {
        $this->httpClient->post(
            'collections/' . $this->collection . '/records',
            [
                'json' => [
                    'id' => $recordId,
                    ...$payload,
                ],
            ],
        );
    }

    private function updateRecord($recordId, $payload): void
    {
        $this->httpClient->patch(
            'collections/' . $this->collection . '/records/' . $recordId,
            [
                'json' => [
                    ...$payload,
                ],
            ],
        );
    }

    private function upsertRecord($recordId, $payload): void
    {
        $doc = $this->read($recordId);

        if (!$doc) {
            $this->createRecord($recordId, $payload);
        } else {
            $this->updateRecord($recordId, $payload);
        }
    }

    public function read(string $documentId): array | null
    {
        try {
            $response = $this->httpClient->get(
                'collections/' . $this->collection . '/records/' . $documentId,
            );

            return ($content = $response->getBody()->getContents()) ? json_decode($content, true) : null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function write(string $documentId, array $document, array $options = []): void
    {
        if (isset($options['merge']) && $options['merge'] === false) {
            $this->delete($documentId);
            $this->createRecord($documentId, $document);
        } else {
            $this->upsertRecord($documentId, $document);
        }
    }

    public function delete(string $documentId): void
    {
        try {
            $this->httpClient->delete(
                'collections/' . $this->collection . '/records/' . $documentId,
            );
        } catch (Exception $e) {
            // noop
        }
    }
}
