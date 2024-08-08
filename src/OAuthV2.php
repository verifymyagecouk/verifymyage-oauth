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
    public function getStartVerificationUrl($country, $method="", $businessSettingsId="", $userId="", $verificationId="", $userInfo=""){
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
                "user_id"               => $userId,
                "verification_id"       => $verificationId,
                "user_info"             => $userInfo,
                "redirect_url"          => $this->redirectURL,
            ];
            
            $bodyEncoded        = json_encode($body);
            $vmaHmacSignature   = $this->provider()->generateHmacVmaSignature($bodyEncoded);
            $url                = $this->provider()->getBaseAuthorizationUrl();
            $basicAuth          = $this->provider()->getBasicAuthorization();
            $client             = new Client();

            $response           = $client->request('POST', $url, [
                'headers' => [
                    'Authorization'         => $basicAuth,
                    'VMA-HMAC-Signature'    => $vmaHmacSignature,
                    'Content-Type'          => 'application/json',
                ],
                'body' => $bodyEncoded,
    
            ]);

            $responseBodyDecode = json_decode($response->getBody()->getContents(), true);
    
            
            return $responseBodyDecode['message'];

        }catch (\Exception $e) {
            throw new \Exception("Error on get start verification url. Message: " . $e->getMessage());
        
        }
       
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
