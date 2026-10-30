# Security & Architecture

## Cryptographic JWT Validation (EdDSA / Ed25519)

All incoming requests from Torob to your shop carry an asymmetric JWT in the request headers:

- `X-Torob-Token`: The base64-encoded JWT token string.
- `X-Torob-Token-Version`: Must be `1`.

### Native PHP Sodium Implementation

Rather than introducing heavy third-party JWT libraries that require frequent maintenance, `torob-laravel` uses PHP's native `ext-sodium` extension (`sodium_crypto_sign_verify_detached`).

The package validates:
1. **Algorithm Check**: Strictly verifies that the header `alg` equals `EdDSA`.
2. **Signature**: Verifies the signature against Torob's 32-byte Ed25519 public key.
3. **Expiration (`exp`)**: Rejects requests where `time() > exp`.
4. **Not-Before (`nbf`)**: Rejects requests where `time() < nbf`.
5. **Audience (`aud`)**: Compares `aud` against your server's `Host` header (including custom port if specified), preventing tokens issued for other shops from being replayed against your shop.

### Middleware Execution

The `VerifyTorobToken` middleware (`torob.auth`) guards all inbound routes:

```php
// Decoded JWT payload is accessible inside controllers:
$payload = $request->attributes->get('torob_token');
```

If validation fails, the middleware returns HTTP `401 Unauthorized` with:

```json
{
  "error": "Unauthorized: Audience mismatch"
}
```
