# VerifyMyAge PHP SDK

A PHP SDK for integrating with VerifyMyAge's verification service. This library provides easy-to-use methods for implementing age verification in your PHP applications.

## Table of Contents
- [Installation](#installation)
- [Features](#features)
- [Usage](#usage)
  - [Basic Setup](#basic-setup)
  - [Available Methods](#available-methods)
  - [Verification Methods](#verification-methods)
  - [Verification Status](#verification-status-oauthv1--oauthv2)
  - [Supported Countries](#supported-countries)
- [Examples](#examples)
- [Development Mode](#development-mode)

## Installation

```bash
composer require verifymyagecouk/verifymyage-oauth
```

## Features

- Authentication flow
- Multiple verification methods
- Support for various countries
- Sandbox environment for testing
- HMAC authentication
- User data encryption

## Usage

### Basic Setup

```php
use VerifyMyAge\OAuth;

// Initialize the OAuth client
$oauth = new OAuth(
    'your-client-id',
    'your-client-secret',
    'your-redirect-url'
);

// For development/testing, use sandbox mode
$oauth->useSandbox();
```

### Available Methods

The SDK provides three different versions of the OAuth implementation:

1. **OAuth (Legacy)**:
```php
use VerifyMyAge\OAuth;
$oauth = new OAuth($clientId, $clientSecret, $redirectUrl);
```

2. **OAuthV1**: 
```php
use VerifyMyAge\OAuthV1;
$oauth = new OAuthV1($clientId, $clientSecret, $redirectUrl);
```

3. **OAuthV2** (Recommended):
```php
use VerifyMyAge\OAuthV2;
$oauth = new OAuthV2($clientId, $clientSecret, $redirectUrl);
```

### Verification Methods

Available verification methods:
```php
Methods::AGE_ESTIMATION
Methods::CREDIT_CARD
Methods::ID_SCAN
Methods::ID_SCAN_FACE_MATCH
Methods::EMAIL
```

### Starting Verification (OAuthV2)

```php
$result = $oauth->getStartVerificationUrl(
    country: Countries::UNITED_KINGDOM,
    method: Methods::ID_SCAN,
    businessSettingsId: 'your-business-settings-id',
    externalUserId: 'user-123',
    verificationId: 'verification-123',
    webhook: 'https://your-webhook.com/callback',
    stealth: false,
    userInfo: [
        'email' => 'user@example.com'
        // Additional user information
    ]
);
```

### Handling the OAuth Flow

1. **Generate Authorization URL**:
```php
$authUrl = $oauth->redirectURL(
    country: Countries::UNITED_KINGDOM,
    method: Methods::ID_SCAN
);
```

2. **Exchange Code for Token**:

After the user completes the verification process with us, we will redirect the user back to you
using your redirect URL provided to us in the first step, we will keep all the query strings you've sent and also
add two new ones, First as **code** and Second as **verification_id**.
The **code** must be used in this function, so we can authenticate your request and identify the verification 
that you want to get the result.

```php
$token = $oauth->exchangeCodeByToken($code);
```

### Supported Countries

The SDK supports various countries including:
- United Kingdom (`Countries::UNITED_KINGDOM`)
- France (`Countries::FRANCE`)
- Germany (`Countries::GERMANY`)
- United States (`Countries::UNITED_STATES_OF_AMERICA`)
- Demo mode (`Countries::DEMO`)

## Development Mode

For testing and development, use the sandbox environment:

```php
$oauth->useSandbox();
```

This will direct all requests to the sandbox API endpoint.

## Complete Example

Here's a complete example of implementing age verification:

```php
<?php

use VerifyMyAge\OAuthV2;
use VerifyMyAge\Countries;
use VerifyMyAge\Methods;

// Initialize the OAuth client
$oauth = new OAuthV2(
    clientID: 'your-client-id',
    clientSecret: 'your-client-secret',
    redirectURL: 'https://your-app.com/callback'
);

// Use sandbox for development
$oauth->useSandbox();

// Start verification process
$verificationResult = $oauth->getStartVerificationUrl(
    country: Countries::UNITED_KINGDOM,
    method: Methods::ID_SCAN,
    businessSettingsId: 'your-business-id',
    externalUserId: 'user-123',
    webhook: 'https://your-app.com/webhook'
);

// Handle the verification response
if (isset($verificationResult['verification_url'])) {
    // Redirect user to verification URL
    header('Location: ' . $verificationResult['verification_url']);
    exit;
}
```

### Verification Status (OAuthV1 & OAuthV2)

For detailed information about how to check the verification status, please refer to the official documentation:
[Verification Status Documentation](https://docs.verifymyage.com/docs/adult/authorisation/#retrieve-verification-status)

## Security Considerations

- Always use HTTPS for redirect URLs
- Keep your client credentials secure
- Validate all user input
- Implement proper error handling
- Use webhook validation for callbacks

## Error Handling

The SDK throws exceptions for invalid inputs or API errors. Always wrap API calls in try-catch blocks:

```php
try {
    $result = $oauth->getStartVerificationUrl(...);
} catch (\Exception $e) {
    // Handle the error appropriately
    error_log($e->getMessage());
}
```