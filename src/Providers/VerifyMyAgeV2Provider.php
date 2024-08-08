<?php

namespace VerifyMyAge\Providers;

use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;
use League\OAuth2\Client\Provider\GenericResourceOwner;

class VerifyMyAgeV2Provider extends \League\OAuth2\Client\Provider\AbstractProvider {

    use BearerAuthorizationTrait;

    const ACCESS_TOKEN_RESOURCE_OWNER_ID = null;
    
    private $baseURL = "https://oauth.verifymyage.com";

    public function useSandbox(){
       $this->baseURL = "https://oauth.sandbox.verifymyage.com";
    }

    public function getBaseAuthorizationUrl(){
        return "{$this->baseURL}/v1/auth/start";
    }

    public function getBaseAccessTokenUrl(array $params) {
        return "{$this->baseURL}/token";
    }

    public function getBasicAuthorization() { 
        return base64_encode("Basic $this->clientId:$this->clientSecret");
    }

    public function generateHmacVmaSignature($body)
    { 
        return hash_hmac('sha256', $body, $this->clientSecret);
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token) {
        return "{$this->baseURL}/users/me";
    }
    
    public function getDefaultScope() {
        return 'adult';
    }

    protected function getDefaultScopes() {
        return ['adult'];
    }

    protected function checkResponse(ResponseInterface $response, $data) {

    }
    
    protected function createResourceOwner(array $response, AccessToken $token) {
        return new GenericResourceOwner($response, null);
    }
}