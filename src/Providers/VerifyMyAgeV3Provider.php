<?php

namespace VerifyMyAge\Providers;

class VerifyMyAgeV3Provider
{
    private $baseURL = "https://oauth.verifymyage.com";

    private $clientId;

    private $clientSecret;

    public function __construct(array $options)
    {
        $this->clientId     = $options['clientId'];
        $this->clientSecret = $options['clientSecret'];
    }

    public function useSandbox()
    {
        $this->baseURL = "https://oauth.sandbox.verifymyage.com";
    }

    public function getStartVerificationUrl()
    {
        return "{$this->baseURL}/api/v3/verifications";
    }

    public function getVerificationUrl(string $verificationId)
    {
        return "{$this->baseURL}/api/v3/verifications/{$verificationId}";
    }

    public function getAllowedRedirectsUrl()
    {
        return "{$this->baseURL}/api/v3/allowed-redirects";
    }

    /**
     * HMAC authorization for POST/PUT requests (signs the request body).
     */
    public function generateHMACAuthorization(string $body)
    {
        $signature = hash_hmac('sha256', $body, $this->clientSecret);
        return "hmac {$this->clientId}:{$signature}";
    }

    /**
     * HMAC authorization for GET requests (signs the request URI path).
     */
    public function generateHMACAuthorizationForGet(string $uriPath)
    {
        $signature = hash_hmac('sha256', $uriPath, $this->clientSecret);
        return "hmac {$this->clientId}:{$signature}";
    }

    public function getUserInfoEncoded(array $userInfo)
    {
        $userInfo['email'] = $this->encrypt($userInfo['email']);
        return $userInfo;
    }

    private function encrypt(string $text)
    {
        $secretHash        = substr(hash('sha256', $this->clientSecret, true), 0, 32);
        $iv                = openssl_random_pseudo_bytes(16);
        $ciphertext        = openssl_encrypt($text, 'AES-256-CFB', $secretHash, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $ciphertext);
    }
}
