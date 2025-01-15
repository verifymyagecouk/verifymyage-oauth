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
     * Do a post with HMAC authorization to VerifyMy OAuthV2 and return response from service.
     */
    public function getStartVerificationUrl(string $country, string $method="", string $businessSettingsId="", string $externalUserId="", string $verificationId="", string $webhook="",bool $stealth=false, bool $runOtp=false, array $userInfo=array()){
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
                "otp_webhook"               => $webhook,
            ];
            if (count($userInfo)) {
                $body['user_info'] = $this->provider()->getUserInfoEncoded($userInfo);
            }
            $bodyEncoded        = json_encode($body);
            $authorization      = $this->provider()->generateHMACAutorization($bodyEncoded);
            $url                = $this->provider()->getBaseAuthorizationUrl();
            $urlWithQueryParam  = "{$url}?stealth={$stealth}&runOTP={$runOtp}";
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
     * Exchange code by an access token
     */
    public function exchangeCodeByToken($code)
    {
        return $this->provider()->getAccessToken('authorization_code', [
            'code' => $code,
        ]);
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
