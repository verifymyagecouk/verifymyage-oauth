<?php

namespace VerifyMyAge;

/**
 * VerifyMyAge OAuth2 - Adult Content SDK
 */
class OAuth
{

    const COUNTRIES = [
        Countries::UNITED_KINGDOM,
        Countries::FRANCE,
        Countries::GERMANY,
        Countries::UNITED_STATES_OF_AMERICA,
        Countries::UNITED_STATES_OF_AMERICA_TWO,
    ];

    const METHODS = [
        Methods::AGE_ESTIMATION,
        Methods::CREDIT_CARD,
        Methods::ID_SCAN,
        Methods::ID_SCAN_FACE_MATCH,
        Methods::EMAIL,
    ];
    
    private $clientID;

    private $clientSecret;

    private $redirectURL;

    private $currentState;

    private $currentProvider;

    public function __construct($clientID, $clientSecret, $redirectURL)
    {
        $this->clientID = $clientID;
        $this->clientSecret = $clientSecret;
        $this->redirectURL = $redirectURL;
        $this->currentState = null;
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
     * URL to be redirect your user after the age-gate
     */
    public function redirectURL($country, $method=NULL)
    {
        if (!in_array($country, static::COUNTRIES)) {
            throw new \Exception("Invalid country: ${country}");
        }

        $arrayToAuthorizationUrl = [
            "scope"     => "adult",
            "state"     => $this->state(),
            "country"   => $country
        ];

        if($method && !in_array($method, static::METHODS)){
            throw new \Exception("Invalid method: ${method}");
        }

        $arrayToAuthorizationUrl['method'] = $method;
        return $this->provider()->getAuthorizationUrl($arrayToAuthorizationUrl);
    }

    /**
     * OAuth state parameter used to avoid CSRF attacks
     */
    public function state()
    {
        if (null === $this->currentState) {
            $this->currentState = uniqid();
        }
        return $this->currentState;
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
     * Return user data
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
            $this->currentProvider = new Providers\VerifyMyAgeProvider([
                'clientId'                 => $this->clientID,
                'clientSecret'             => $this->clientSecret,
                'redirectUri'              => $this->redirectURL,
            ]);
        }
        return $this->currentProvider;
    }
}
