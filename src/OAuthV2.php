<?php


namespace VerifyMyAge;

use GuzzleHttp\Client;

/**
 * VerifyMyAge OAuth2 - Adult Content SDK
 */
class OAuthV2
{
    private $clientID;

    private $clientSecret;

    private $redirectURL;

    private $currentProvider;

    const METHODS = [
        Methods::AGE_ESTIMATION,
        Methods::CREDIT_CARD,
        Methods::ID_SCAN,
        Methods::ID_SCAN_FACE_MATCH,
        Methods::EMAIL,
    ];

    const COUNTRIES = [
        Countries::UNITED_KINGDOM,
        Countries::FRANCE,
        Countries::GERMANY,
        Countries::UNITED_STATES_OF_AMERICA,
        Countries::DEMO,
    ];

    public function __construct($clientID, $clientSecret, $redirectURL)
    {
        $this->clientID = $clientID;
        $this->clientSecret = $clientSecret;
        $this->redirectURL = $redirectURL;
        $this->currentProvider = null;

    }

    /**
     * If you're still in development stages, you can use our sandbox environment
     */
    public function useSandbox()
    {
        $this->provider()->useSandbox();
    }

    /**
     * Generate a pre authorization with the /me endpoint and return a response from service.
     *
     * @param string $authToken
     * @return array
     */
    public function getVerificationStatusPreAuth(string $authToken)
    {
        try {
            $authorization      = $this->provider()->generateHMACAutorization('');
            $url                = $this->provider()->getBaseMeUrl($authToken);
            $response           = $client->request('GET', $url, [
                'headers' => [
                    'Authorization'         => $authorization,
                    'Content-Type'          => 'application/json',
                ]
            ]);

            $responseBodyDecode = json_decode($response->getBody()->getContents(), true);
            return $responseBodyDecode;

        } catch (\Exception $e) {
            throw new \Exception("Error on get verification status pre auth. Message: {$e->getMessage()}");

        }
    }

    /**
     * Check the status of a verification and return a response from service.
     *
     * @param string $id
     * @return array
     */
    public function getVerificationStatusPostAuth(string $id)
    {
        try {
            $authorization      = $this->provider()->generateHMACAutorization('');
            $url                = $this->provider()->getBaseStatusUrl($id);
            $response           = $client->request('GET', $url, [
                'headers' => [
                    'Authorization'         => $authorization,
                    'Content-Type'          => 'application/json',
                ]
            ]);

            $responseBodyDecode = json_decode($response->getBody()->getContents(), true);
            return $responseBodyDecode;

        } catch (\Exception $e) {
            throw new \Exception("Error on get verification status. Message: {$e->getMessage()}");

        }
    }

    /**
     * An opinionated wrapper for getting a pre-authorization and the status of a verification.
     *
     * @param string $authToken
     * @return array|null
     */
    public function getVerificationStatus(string $authToken)
    {
        $pre_auth        = $this->getVerificationStatusPreAuth($authToken);

        if (isset($pre_auth['age_verified']) && $pre_auth['age_verified'] == true && !empty($pre_auth['id'])) {
            return $this->getVerificationStatusPostAuth($pre_auth['id']);
        }

        return null;
    }

    /**
     * Do a post with HMAC authorization to VerifyMy OAuthV2 and return response from service.
     */
    public function getStartVerificationUrl(string $country, string $method="", string $businessSettingsId="", string $externalUserId="", string $verificationId="", string $webhook="",bool $stealth=false, array $userInfo=array()){
        if (!in_array($country, static::COUNTRIES)) {
            throw new \Exception("Invalid country: " . $country);
        }

        if($method && !in_array($method, static::METHODS)){
            throw new \Exception("Invalid method: ". $method);
        }

        try {
            $body = [
                "scope"                 => $this->provider()->getDefaultScope(),
                "country"               => $country,
                "method"                => $method,
                "business_settings_id"  => $businessSettingsId,
                "external_user_id"      => $externalUserId,
                "verification_id"       => $verificationId,
                "redirect_url"          => $this->redirectURL,
                "webhook"               => $webhook,
            ];
            if (count($userInfo)) {
                $body['user_info'] = $this->provider()->getUserInfoEncoded($userInfo);
            }
            $bodyEncoded        = json_encode($body);
            $authorization      = $this->provider()->generateHMACAutorization($bodyEncoded);
            $url                = $this->provider()->getBaseAuthorizationUrl();
            $urlWithQueryParam  = "{$url}?stealth={$stealth}";
            $client             = new Client();
            $response           = $client->request('POST', $urlWithQueryParam, [
                'headers' => [
                    'Authorization'         => $authorization,
                    'Content-Type'          => 'application/json',
                ],
                'body' => $bodyEncoded,

            ]);
            $responseBodyDecode = json_decode($response->getBody()->getContents(), true);
            return $responseBodyDecode;

        }catch (\Exception $e) {
            throw new \Exception("Error on get start verification url. Message: " . $e->getMessage());

        }

    }

     /**
     * After the user completes the verification process with us, we will redirect the user back to you
     * using your redirect URL provided to us in the first step, we will keep all the query strings you've sent and also
     * add two new ones, First as **code** and Second as **verification_id**.
     * The **code** must be used in this function, so we can authenticate your request and identify the verification
     * that you want to get the result.
     */
    public function exchangeCodeByToken($code)
    {
        $response = $this->provider()->getAccessToken('authorization_code', [
            'code' => $code,
        ]);

        return [
            'accessToken' => $response->getToken(),
            'expires' => $response->getExpires(),
            'refreshToken' => $response->getRefreshToken(),
            'values' => $response->getValues()
        ];
    }

    /**
     * Return user data from an access token
     */
    public function user($accessToken)
    {
        return $this->provider()->getResourceOwner($accessToken)->toArray();
    }

    /**
     * VMA Provider
     */
    private function provider()
    {
        if ($this->currentProvider === null) {
            $this->currentProvider = new Providers\VerifyMyAgeV2Provider([
                'clientId'                 => $this->clientID,
                'clientSecret'             => $this->clientSecret,
                'redirectUri'              => $this->redirectURL,

            ]);
        }
        return $this->currentProvider;
    }
}
