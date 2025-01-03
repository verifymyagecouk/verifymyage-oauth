# VerifyMyAge OAuth Adult Content PHP SDK

PHP SDK to use VerifyMyAge OAuth service. 

## Installation
1. PHP ^7.2.5 || ^8.0
2. Composer ^2.0
```bash
composer require verifymyagecouk/verifymyage-oauth
```

## Usage Examples
 - [Documentation](https://docs.verifymyage.com/docs/adult/authorisation/) of OAuthV2 version.
```php
<?php  
require(__DIR__ . "/vendor/autoload.php");
// To use OAuthV2
$vma = new VerifyMyAge\OAuthV2(getenv('VMA_CLIENT_ID'), getenv('VMA_CLIENT_SECRET'), getenv('VMA_REDIRECT_URL'));
//$vma->useSandbox();
// Will return an array with index: start_verification_url, verification_id and verification_status
$response = $vma->getStartVerificationUrl(VerifyMyAge\Countries::UNITED_STATES_OF_AMERICA);
print_r($response);

// Avoid CSRF attack
if (empty($_GET['state']) || (isset($_SESSION['oauth2state']) && $_GET['state'] !== $_SESSION['oauth2state'])) {
    if (isset($_SESSION['oauth2state'])) {
        unset($_SESSION['oauth2state']);
    }
    exit('Invalid state');
} else {
    try {
        // Try to get an access token using the authorization code grant.
        $accessToken = $vma->exchangeCodeByToken($_GET['code']);
        $user = $vma->user($accessToken);
        var_export($user);
    } catch (\Exception $e) {
        // Failed to get the access token or user details.
        exit($e->getMessage());
    }
}
```

- [Documentation](https://docs.verifymyage.com/docs/adult/oauth2/) of OAuth version (DEPECRETED).
```php
<?php

require(__DIR__ . "/vendor/autoload.php");

$vma = new VerifyMyAge\OAuth(getenv('VMA_CLIENT_ID'), getenv('VMA_CLIENT_SECRET'), getenv('VMA_REDIRECT_URL'));
//$vma->useSandbox();
// Redirect or show age-gate if we don't have a code yet
if (!isset($_GET['code'])) {
    $redirectURL = $vma->redirectURL(VerifyMyAge\Countries::GERMANY);
    $_SESSION['oauth2state'] = $vma->state();
    header('Location: ' . $redirectURL);
    exit;

// Avoid CSRF attack
} elseif (empty($_GET['state']) || (isset($_SESSION['oauth2state']) && $_GET['state'] !== $_SESSION['oauth2state'])) {
    if (isset($_SESSION['oauth2state'])) {
        unset($_SESSION['oauth2state']);
    }
    exit('Invalid state');
} else {
    try {
        // Try to get an access token using the authorization code grant.
        $accessToken = $vma->exchangeCodeByToken($_GET['code']);
        $user = $vma->user($accessToken);
        var_export($user);
    } catch (\Exception $e) {
        // Failed to get the access token or user details.
        exit($e->getMessage());
    }
}
```

**Country Options**

VerifyMyAge\Countries::FRANCE

VerifyMyAge\Countries::GERMANY

VerifyMyAge\Countries::UNITED_KINGDOM

VerifyMyAge\Countries::UNITED_STATES_OF_AMERICA
