<?php

namespace VerifyMyAge;

use GuzzleHttp\Client;

/**
 * VerifyMyAge OAuth - Age Assurance SDK (API v3)
 */
class OAuthV3
{
    private $clientID;

    private $clientSecret;

    private $redirectURL;

    private $currentProvider;

    const METHODS = [
        Methods::AGE_ESTIMATION,
        Methods::CREDIT_CARD,
        Methods::DOUBLE_BLIND,
        Methods::EMAIL,
        Methods::ID_SCAN,
        Methods::ID_SCAN_FACE_MATCH,
    ];

    const COUNTRIES = [
        Countries::AUSTRALIA,
        Countries::BRAZIL,
        Countries::SPAIN,
        Countries::UNITED_KINGDOM,
        Countries::FRANCE,
        Countries::GERMANY,
        Countries::IRELAND,
        Countries::ITALY,
        Countries::UNITED_STATES_OF_AMERICA,
        Countries::INDONESIA,
        Countries::DEMO,
    ];

    const WEBHOOK_NOTIFICATION_LEVELS = [
        Webhook::MINIMAL_NOTIFICATION_LEVEL,
        Webhook::METHOD_EXHAUSTED_V3_NOTIFICATION_LEVEL,
        Webhook::DETAILED_NOTIFICATION_LEVEL,
    ];

    public function __construct($clientID, $clientSecret, $redirectURL)
    {
        $this->clientID       = $clientID;
        $this->clientSecret   = $clientSecret;
        $this->redirectURL    = $redirectURL;
        $this->currentProvider = null;
    }

    /**
     * Switch to the sandbox environment for testing.
     */
    public function useSandbox()
    {
        $this->provider()->useSandbox();
    }

    /**
     * Start a new verification session (POST /api/v3/verifications).
     *
     * Returns the API response array. When the user can be instantly approved the
     * response contains only `verification_id` and `verification_status`. When user
     * interaction is required it also contains `start_verification_url`.
     */
    public function getStartVerificationUrl(
        string $country,
        string $method = "",
        string $businessSettingsId = "",
        string $externalUserId = "",
        string $webhook = "",
        string $webhookNotificationLevel = "",
        array $userInfo = []
    ) {
        if (!in_array($country, static::COUNTRIES)) {
            throw new \Exception("Invalid country: " . $country);
        }

        if ($method && !in_array($method, static::METHODS)) {
            throw new \Exception("Invalid method: " . $method);
        }

        if ($webhookNotificationLevel && !in_array($webhookNotificationLevel, static::WEBHOOK_NOTIFICATION_LEVELS)) {
            throw new \Exception("Invalid webhook notification level: " . $webhookNotificationLevel);
        }

        try {
            $body = array_filter([
                "redirect_url"               => $this->redirectURL,
                "country"                    => $country,
                "method"                     => $method,
                "business_settings_id"       => $businessSettingsId,
                "external_user_id"           => $externalUserId,
                "webhook"                    => $webhook,
                "webhook_notification_level" => $webhookNotificationLevel,
            ], fn($v) => $v !== null && $v !== '');

            if (count($userInfo)) {
                $body['user_info'] = $this->provider()->getUserInfoEncoded($userInfo);
            }

            $bodyEncoded   = json_encode($body);
            $authorization = $this->provider()->generateHMACAuthorization($bodyEncoded);
            $url           = $this->provider()->getStartVerificationUrl();
            $client        = new Client();
            $response      = $client->request('POST', $url, [
                'headers' => [
                    'Authorization' => $authorization,
                    'Content-Type'  => 'application/json',
                ],
                'body' => $bodyEncoded,
            ]);

            return json_decode($response->getBody()->getContents(), true);

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response   = $e->getResponse();
            $statusCode = $response->getStatusCode();
            $jsonObject = json_decode($response->getBody()->getContents(), true);
            $jsonObject['code'] = $statusCode;
            throw new \Exception(json_encode($jsonObject));
        }
    }

    /**
     * Retrieve the current status and details of a verification (GET /api/v3/verifications/{id}).
     *
     * Call this after the user is redirected back to your redirect URL with a
     * `verification_id` query parameter to obtain the final result.
     */
    public function getVerification(string $verificationId)
    {
        try {
            $url       = $this->provider()->getVerificationUrl($verificationId);
            $uriPath   = parse_url($url, PHP_URL_PATH);
            $authorization = $this->provider()->generateHMACAuthorizationForGet($uriPath);
            $client    = new Client();
            $response  = $client->request('GET', $url, [
                'headers' => [
                    'Authorization' => $authorization,
                    'Content-Type'  => 'application/json',
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response   = $e->getResponse();
            $statusCode = $response->getStatusCode();
            $jsonObject = json_decode($response->getBody()->getContents(), true);
            $jsonObject['code'] = $statusCode;
            throw new \Exception(json_encode($jsonObject));
        }
    }

    /**
     * Get all allowed redirect URLs registered for this account (GET /api/v3/allowed-redirects).
     */
    public function getAllowedRedirects()
    {
        try {
            $url       = $this->provider()->getAllowedRedirectsUrl();
            $uriPath   = parse_url($url, PHP_URL_PATH);
            $authorization = $this->provider()->generateHMACAuthorizationForGet($uriPath);
            $client    = new Client();
            $response  = $client->request('GET', $url, [
                'headers' => [
                    'Authorization' => $authorization,
                    'Content-Type'  => 'application/json',
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response   = $e->getResponse();
            $statusCode = $response->getStatusCode();
            $jsonObject = json_decode($response->getBody()->getContents(), true);
            $jsonObject['code'] = $statusCode;
            throw new \Exception(json_encode($jsonObject));
        }
    }

    /**
     * Append one or more redirect URLs to the allowed list (PUT /api/v3/allowed-redirects).
     *
     * This appends to the existing list — it does not replace it.
     * All URLs must use HTTPS.
     */
    public function addAllowedRedirects(array $urls)
    {
        try {
            $bodyEncoded   = json_encode($urls);
            $authorization = $this->provider()->generateHMACAuthorization($bodyEncoded);
            $url           = $this->provider()->getAllowedRedirectsUrl();
            $client        = new Client();
            $response      = $client->request('PUT', $url, [
                'headers' => [
                    'Authorization' => $authorization,
                    'Content-Type'  => 'application/json',
                ],
                'body' => $bodyEncoded,
            ]);

            return json_decode($response->getBody()->getContents(), true);

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response   = $e->getResponse();
            $statusCode = $response->getStatusCode();
            $jsonObject = json_decode($response->getBody()->getContents(), true);
            $jsonObject['code'] = $statusCode;
            throw new \Exception(json_encode($jsonObject));
        }
    }

    private function provider()
    {
        if ($this->currentProvider === null) {
            $this->currentProvider = new Providers\VerifyMyAgeV3Provider([
                'clientId'     => $this->clientID,
                'clientSecret' => $this->clientSecret,
            ]);
        }
        return $this->currentProvider;
    }
}
