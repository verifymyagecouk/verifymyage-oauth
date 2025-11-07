<?php

namespace VerifyMyAge\Providers;

use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;
use League\OAuth2\Client\Provider\GenericResourceOwner;

class VerifyMyAgeV1Provider extends \League\OAuth2\Client\Provider\AbstractProvider {

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
        $basicAuth = base64_encode("$this->clientId:$this->clientSecret");
        return "Basic {$basicAuth}";
    }

    public function getUserInfoEncoded($userInfo){
        $userInfo['email']  = self::encrypt($userInfo['email']);
        return $userInfo;
    }

    public function generateHmacVmaSignature($body)
    { 
        $VMASignature = hash_hmac('sha256', $body, $this->clientSecret);
        return "hmac {$VMASignature}";
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

    protected function encrypt($text)
    {
        $secretHash         = substr(hash('sha256', $this->clientSecret, true), 0, 32);
        $iv                 = openssl_random_pseudo_bytes(16);
        $ciphertext         = openssl_encrypt($text, 'AES-256-CFB', $secretHash, OPENSSL_RAW_DATA, $iv);
        $ciphertext_base64  = base64_encode($iv . $ciphertext);    
        return $ciphertext_base64;
    }

}