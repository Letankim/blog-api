<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use GuzzleHttp\Client;

class NotificationService
{
    private string $projectId;
    private string $baseUrl;
    private array $config;
    private Client $client;
    private $auth;

    public function __construct()
    {
        $serviceAccountPath = __DIR__ . "/../../firebase-service-account.json";
        $envCreds = getenv('FIREBASE_CREDENTIALS');

        if (file_exists($serviceAccountPath)) {
            $this->config = json_decode(file_get_contents($serviceAccountPath), true) ?? [];
        } elseif ($envCreds) {
            $this->config = json_decode($envCreds, true) ?? [];
        } else {
            $this->config = [];
        }

        $this->projectId = $this->config['project_id'] ?? '';
        
        if (!empty($this->projectId)) {
            $this->baseUrl   = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents";
            $this->auth = new ServiceAccountCredentials(
                ['https://www.googleapis.com/auth/datastore'],
                $this->config
            );
        }

        $this->client = new Client();
    }

    private function getAccessToken()
    {
        $token = $this->auth->fetchAuthToken();
        return $token['access_token'];
    }

    private function firestoreUrl(string $collection)
    {
        return "{$this->baseUrl}/{$collection}";
    }

    public function send(string $userId, string $type, array $message = [])
    {
        if (empty($this->projectId)) return;
        $url = $this->firestoreUrl("notifications");

        $data = [
            "fields" => [
                "userId" => ["stringValue" => $userId],
                "type"   => ["stringValue" => $type],
                "title"  => ["stringValue" => $message["title"] ?? ""],
                "body"   => ["stringValue" => $message["body"] ?? ""],
                "read"   => ["booleanValue" => false],
                "createdAt" => (new \DateTime())->format('c')
            ]
        ];

        $this->client->post($url, [
            "headers" => [
                "Authorization" => "Bearer " . $this->getAccessToken(),
                "Content-Type"  => "application/json",
            ],
            "json" => $data
        ]);
    }

    public function sendToAdminChannel(string $type, array $message = [])
    {
        if (empty($this->projectId)) return;
        $url = $this->firestoreUrl("notifications_admin");

        $data = [
            "fields" => [
                "type"  => ["stringValue" => $type],
                "title" => ["stringValue" => $message["title"] ?? ""],
                "body"  => ["stringValue" => $message["body"] ?? ""],
                "data"  => $this->toFirestoreValue($message["data"] ?? []),
                "read"  => ["booleanValue" => false],
                "createdAt" => ["timestampValue" => (new \DateTime())->format('c')]
            ]
        ];

        $this->client->post($url, [
            "headers" => [
                "Authorization" => "Bearer " . $this->getAccessToken(),
                "Content-Type"  => "application/json",
            ],
            "json" => $data
        ]);
    }


     public function sendOrderToGuestChannel(string $type, array $message = [])
    {
        if (empty($this->projectId)) return;
        $url = $this->firestoreUrl("notifications_order_marketings");

        $data = [
            "fields" => [
                "type"  => ["stringValue" => $type],
                "title" => ["stringValue" => $message["title"] ?? ""],
                "body"  => ["stringValue" => $message["body"] ?? ""],
                "data"  => $this->toFirestoreValue($message["data"] ?? []),
                "read"  => ["booleanValue" => false],
                "createdAt" => ["timestampValue" => (new \DateTime())->format('c')]
            ]
        ];

        $this->client->post($url, [
            "headers" => [
                "Authorization" => "Bearer " . $this->getAccessToken(),
                "Content-Type"  => "application/json",
            ],
            "json" => $data
        ]);
    }


    private function toFirestoreValue($value)
{
    if (is_string($value)) {
        return ["stringValue" => $value];
    }

    if (is_bool($value)) {
        return ["booleanValue" => $value];
    }

    if (is_int($value)) {
        return ["integerValue" => $value];
    }

    if (is_float($value)) {
        return ["doubleValue" => $value];
    }

    if (is_array($value)) {
        if ($this->isAssoc($value)) {
            return [
                "mapValue" => [
                    "fields" => array_map(fn($v) => $this->toFirestoreValue($v), $value)
                ]
            ];
        }

        return [
            "arrayValue" => [
                "values" => array_map(fn($v) => $this->toFirestoreValue($v), $value)
            ]
        ];
    }

    return ["nullValue" => null];
}

private function isAssoc(array $arr)
{
    return array_keys($arr) !== range(0, count($arr) - 1);
}

    public function markAsRead(string $documentId, bool $isAdmin = false)
    {
        if (empty($this->projectId)) return;
        $collection = $isAdmin ? "notifications_admin" : "notifications";
        $url = "{$this->baseUrl}/{$collection}/{$documentId}?updateMask.fieldPaths=read";

        $data = [
            "fields" => [
                "read" => ["booleanValue" => true]
            ]
        ];

        $this->client->patch($url, [
            "headers" => [
                "Authorization" => "Bearer " . $this->getAccessToken(),
                "Content-Type"  => "application/json",
            ],
            "json" => $data
        ]);
    }
}
