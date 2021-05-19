# VerifyMyAge OAuth Adult Content PHP SDK

PHP SDK to use VerifyMyAge OAuth service. 

## Installation

```bash
composer require verifymyagecouk/verifymyage-oauth
```

## Get Started

```php
<?php

require(__DIR__ . "/vendor/autoload.php");

$vma = new VerifyMyAge\OAuth(getenv('VMA_CLIENT_ID'), getenv('VMA_CLIENT_SECRET'), getenv('VMA_REDIRECT_URL'));
//$vma->useSandbox();

// Redirect or show age-gate if we don't have a code yet
if (!isset($_GET['code'])) {
    $redirectURL = $vma->redirectURL();
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