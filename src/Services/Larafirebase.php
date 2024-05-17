<?php

namespace Seekoya\Larafirebase\Services;

use Google\Client;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Google\Service\FirebaseCloudMessaging;

class Larafirebase
{
    private $title;

    private $body;

    private $image;

    private $additionalData;

    private $token;

    private $topic;

    private $fromRaw;

    public const API_URI = 'https://fcm.googleapis.com/v1/projects/:projectId/messages:send';

    public function withTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    public function withBody($body)
    {
        $this->body = $body;

        return $this;
    }

    public function withImage($image)
    {
        $this->image = $image;

        return $this;
    }

    public function withAdditionalData($additionalData)
    {
        $this->additionalData = $additionalData;

        return $this;
    }

    public function withToken($token)
    {
        $this->token = $token;

        return $this;
    }

    public function withTopic($topic)
    {
        $this->topic = $topic;

        return $this;
    }

    public function fromRaw($fromRaw)
    {
        $this->fromRaw = $fromRaw;

        return $this;
    }

    public function sendNotification()
    {
        if($this->fromRaw) {
            return $this->callApi($this->fromRaw);
        }

        $payload = [
            'message' => [
                'notification' => [
                    'title' => $this->title,
                    'body' => $this->body,
                    'image' => $this->image,
                ],
            ],
        ];

        if($this->token) {
            $payload['message']['token'] = $this->token;
        }

        if($this->topic) {
            $payload['message']['topic'] = $this->topic;
        }

        if($this->additionalData) {
            $payload['message']['data'] = $this->additionalData;
        }

        return $this->callApi($payload);
    }

    /**
     * @return string
     * @throws \Google\Exception
     */
    private function getBearerToken(): string
    {
        $tenant = tenant();
        $cacheKey = 'LARAFIREBASE_AUTH_TOKEN';
        if (!is_null($tenant->firebase_config) && !empty($tenant->firebase_config)) {
            $firebaseCredentials = $tenant->firebase_config;
            $cacheKey .= '_' . $tenant->id;
        } else {
            $firebaseCredentials = config('larafirebase.firebase_credentials');
            $cacheKey .= '_CARDS';
        }

        $client = new Client();
        $client->setAuthConfig($firebaseCredentials);
        $client->addScope(FirebaseCloudMessaging::CLOUD_PLATFORM);

        /* TODO
         * Stefan: 16/05/2024
         * Temporary fix for Cache not supporting tags
         */
        // $savedToken = Cache::get($cacheKey);
        $savedToken = false;

        if (!$savedToken) {
            $accessToken = $this->generateNewBearerToken($client, $cacheKey);
            $client->setAccessToken($accessToken);

            return $accessToken['access_token'];
        }

        $client->setAccessToken($savedToken);

        if (!$client->isAccessTokenExpired()) {
            return json_decode($savedToken)->access_token;
        }

        $newAccessToken = $this->generateNewBearerToken($client, $cacheKey);
        $client->setAccessToken($newAccessToken);
        return $newAccessToken['access_token'];
    }

    /**
     * @param $client
     * @param $cacheKey
     * @return array
     */
    private function generateNewBearerToken($client, $cacheKey): array
    {
        $client->fetchAccessTokenWithAssertion();
        $accessToken = $client->getAccessToken();

        //$tokenJson = json_encode($accessToken);
        //Cache::add($cacheKey, $tokenJson);

        return $accessToken;
    }

    /**
     * @param $fields
     * @return Response
     * @throws \Google\Exception
     */
    private function callApi($fields): Response
    {
        $tenant = tenant();
        if (!is_null($tenant->firebase_project_id) && !empty($tenant->firebase_project_id)) {
            $firebaseProjectId = $tenant->firebase_project_id;
        } else {
            $firebaseProjectId = config('larafirebase.project_id');
        }

        $apiURL = str_replace(':projectId', $firebaseProjectId, self::API_URI);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->getBearerToken()
        ])->post($apiURL, $fields);

        return $response;
    }
}
