# VerifyMyAge PHP SDK

A PHP SDK for integrating with VerifyMyAge's verification service. This library provides easy-to-use methods for implementing age verification in your PHP applications.

## Table of Contents
- [Installation](#installation)
- [Features](#features)
- [Usage](#usage)
  - [SDK Versions](#sdk-versions)
  - [Verification Methods](#verification-methods)
  - [Webhook Notification Levels](#webhook-notification-levels)
  - [Supported Countries](#supported-countries)
- [OAuthV3 (Recommended)](#oauthv3-recommended)
  - [Start a Verification](#start-a-verification)
  - [Get Verification Status](#get-verification-status)
  - [Manage Allowed Redirect URLs](#manage-allowed-redirect-urls)
- [OAuthV2](#oauthv2)
- [OAuthV1 / OAuth (Legacy)](#oauthv1--oauth-legacy)
- [Development Mode](#development-mode)
- [Error Handling](#error-handling)
- [Security Considerations](#security-considerations)

## Installation

```bash
composer require verifymyagecouk/verifymyage-oauth
```

## Features

- API v3 support with direct HMAC authentication
- Multiple verification methods
- Support for various countries
- Sandbox environment for testing
- User data encryption

## Usage

### SDK Versions

| Class | API Version | Status |
|-------|-------------|--------|
| `OAuthV3` | v3 | **Recommended** |
| `OAuthV2` | v2 | Maintained |
| `OAuthV1` | v1 | Maintained |
| `OAuth` | Legacy | Legacy |

### Verification Methods

```php
use VerifyMyAge\Methods;

Methods::AGE_ESTIMATION     // "AgeEstimation"
Methods::CREDIT_CARD        // "CreditCard"
Methods::DOUBLE_BLIND       // "DoubleBlind"  — v3 only
Methods::EMAIL              // "Email"
Methods::ID_SCAN            // "IDScan"
Methods::ID_SCAN_FACE_MATCH // "IDScanFaceMatch"
```

### Webhook Notification Levels

```php
use VerifyMyAge\Webhook;

Webhook::MINIMAL_NOTIFICATION_LEVEL              // "minimal"           — v2 & v3
Webhook::DETAILED_NOTIFICATION_LEVEL             // "detailed"          — v2 & v3
Webhook::METHOD_EXHAUSTED_NOTIFICATION_LEVEL     // "method_exhausted"  — v2
Webhook::METHOD_EXHAUSTED_V3_NOTIFICATION_LEVEL  // "method-exhausted"  — v3
```

### Supported Countries

The SDK supports various countries including:
- Australia (`Countries::AUSTRALIA`)
- Brazil (`Countries::BRAZIL`)
- Spain (`Countries::SPAIN`)
- United Kingdom (`Countries::UNITED_KINGDOM`)
- France (`Countries::FRANCE`)
- Germany (`Countries::GERMANY`)
- United States (`Countries::UNITED_STATES_OF_AMERICA`)
- Indonesia (`Countries::INDONESIA`)
- Ireland (`Countries::IRELAND`)
- Italy (`Countries::ITALY`)
- Demo mode (`Countries::DEMO`)

---

## OAuthV3 (Recommended)

OAuthV3 targets the `/api/v3/verifications` endpoints and uses HMAC authentication directly — there is no OAuth2 code-exchange step. After the user completes verification they are redirected to your `redirect_url` with a `verification_id` query parameter; use `getVerification()` to retrieve the result.

### Start a Verification

```php
use VerifyMyAge\OAuthV3;
use VerifyMyAge\Countries;
use VerifyMyAge\Methods;
use VerifyMyAge\Webhook;

$oauth = new OAuthV3(
    'your-api-key',
    'your-api-secret',
    'https://your-app.com/callback'
);

// Optional: use sandbox for development
$oauth->useSandbox();

$result = $oauth->getStartVerificationUrl(
    country: Countries::UNITED_KINGDOM,
    method: Methods::ID_SCAN,                                   // optional
    businessSettingsId: 'your-business-settings-id',            // optional
    externalUserId: 'user-123',                                 // optional
    webhook: 'https://your-app.com/webhook',                    // optional
    webhookNotificationLevel: Webhook::DETAILED_NOTIFICATION_LEVEL, // optional
    userInfo: ['email' => 'user@example.com'],                  // optional
);

// Instant approval — no user interaction needed
if ($result['verification_status'] === 'approved') {
    // User is already approved
}

// User interaction required — redirect them to the verification URL
if (isset($result['start_verification_url'])) {
    header('Location: ' . $result['start_verification_url']);
    exit;
}
```

### Encrypting User Info Fields

Sensitive values in `userInfo` are expected by the API to be encrypted. How encryption is applied depends on the field:

- **`email` is encrypted automatically.** When you include `email` in `userInfo`, the SDK encrypts it for you before the verification is created. Pass it as a plain string — do **not** encrypt it yourself, or it will be double-encrypted and rejected.
- **Other fields, such as `phone`, are NOT encrypted by the SDK.** The SDK only encrypts the `email` field. If you need to send another field encrypted, you must encrypt it yourself before adding it to `userInfo`, using the same scheme the SDK applies to `email`.

#### Encrypting `phone` (and other fields) manually

The SDK encrypts `email` with **AES-256-CFB**, using a key derived from your API secret. To send `phone` encrypted, replicate that exact scheme:

- **Key:** the first 32 bytes of the raw (binary) SHA-256 hash of your API secret.
- **IV:** 16 random bytes, generated per value.
- **Output:** base64 of the IV concatenated with the ciphertext (`base64(iv . ciphertext)`).

```php
use VerifyMyAge\OAuthV3;
use VerifyMyAge\Countries;

$apiSecret = 'your-api-secret';

// Same algorithm the SDK uses internally for `email`.
function encryptUserInfoField(string $value, string $apiSecret): string
{
    $key        = substr(hash('sha256', $apiSecret, true), 0, 32);
    $iv         = openssl_random_pseudo_bytes(16);
    $ciphertext = openssl_encrypt($value, 'AES-256-CFB', $key, OPENSSL_RAW_DATA, $iv);

    return base64_encode($iv . $ciphertext);
}

$oauth = new OAuthV3(
    'your-api-key',
    $apiSecret,
    'https://your-app.com/callback'
);

$userInfo = [
    'email' => 'user@example.com',                                   // encrypted automatically by the SDK
    'phone' => encryptUserInfoField('+447123456789', $apiSecret),    // you encrypt this yourself
];

$result = $oauth->getStartVerificationUrl(
    country: Countries::UNITED_KINGDOM,
    userInfo: $userInfo,
);
```

> **Important:** Only encrypt fields the SDK does *not* handle. Never pre-encrypt `email` — the SDK already encrypts it, and encrypting it twice will cause the verification to be rejected.

### Get Verification Status

After the user completes verification they are redirected to your callback URL with `?verification_id=abc123`. Use that ID to fetch the result:

```php
$verification = $oauth->getVerification($verificationId);

// Possible statuses: started | pending | approved | failed | expired
echo $verification['status'];
```

### Manage Allowed Redirect URLs

```php
// List all registered redirect URLs for this account
$urls = $oauth->getAllowedRedirects();

// Append new URLs (does not replace the existing list)
$oauth->addAllowedRedirects([
    'https://your-app.com/callback',
    'https://your-app.com/alt-callback',
]);
```

---

## OAuthV2

OAuthV2 targets the `/v2/auth/start` endpoint. After verification the user is redirected with a `code` and `verification_id`; exchange the code for a token to retrieve user data.

```php
use VerifyMyAge\OAuthV2;
use VerifyMyAge\Countries;
use VerifyMyAge\Methods;
use VerifyMyAge\Webhook;

$oauth = new OAuthV2(
    'your-client-id',
    'your-client-secret',
    'https://your-app.com/callback'
);

$oauth->useSandbox(); // optional

// Start verification
$result = $oauth->getStartVerificationUrl(
    country: Countries::UNITED_KINGDOM,
    method: Methods::ID_SCAN,
    businessSettingsId: 'your-business-id',
    externalUserId: 'user-123',
    verificationId: '',
    webhook: 'https://your-app.com/webhook',
    webhookNotificationLevel: Webhook::DETAILED_NOTIFICATION_LEVEL,
    stealth: false,
    userInfo: ['email' => 'user@example.com'],
);

if (isset($result['start_verification_url'])) {
    header('Location: ' . $result['start_verification_url']);
    exit;
}

// On callback: exchange the code for a token
$token = $oauth->exchangeCodeByToken($_GET['code']);

// Retrieve user/verification data
$user = $oauth->user($token);
```

---

## OAuthV1 / OAuth (Legacy)

```php
use VerifyMyAge\OAuthV1;

$oauth = new OAuthV1($clientId, $clientSecret, $redirectUrl);

$result = $oauth->getStartVerificationUrl(
    country: Countries::UNITED_KINGDOM,
    method: Methods::ID_SCAN,
);

// On callback: exchange the code for a token
$token = $oauth->exchangeCodeByToken($_GET['code']);
```

The legacy `OAuth` class uses the OAuth2 authorization-code redirect flow:

```php
use VerifyMyAge\OAuth;

$oauth = new OAuth($clientId, $clientSecret, $redirectUrl);

// Redirect user to the VerifyMyAge authorization page
$authUrl = $oauth->redirectURL(Countries::UNITED_KINGDOM, Methods::ID_SCAN);
header('Location: ' . $authUrl);
exit;

// On callback: exchange the code for a token
$token = $oauth->exchangeCodeByToken($_GET['code']);
$user  = $oauth->user($token);
```

---

## Development Mode

All SDK versions support sandbox mode. Use it during development to avoid affecting production data:

```php
$oauth->useSandbox();
```

Sandbox endpoint: `https://oauth.sandbox.verifymyage.com`  
Production endpoint: `https://oauth.verifymyage.com`

---

## Error Handling

The SDK throws `\Exception` for invalid inputs and API errors. API errors include the HTTP status code in the exception message as JSON:

```php
try {
    $result = $oauth->getStartVerificationUrl(country: Countries::UNITED_KINGDOM);
} catch (\Exception $e) {
    $error = json_decode($e->getMessage(), true);
    // $error['code'] contains the HTTP status code (for API errors)
    error_log($e->getMessage());
}
```

---

## Security Considerations

- Always use HTTPS for redirect URLs and webhook endpoints
- Keep your API credentials secure and out of version control
- Validate the `verification_id` received in callbacks before using it
- Use webhook signature verification where available
