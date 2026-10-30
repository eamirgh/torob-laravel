<?php

namespace Torob\Laravel\Security;

use Torob\Laravel\Exceptions\TorobTokenException;

class TorobTokenVerifier
{
    private string $rawPublicKey;

    public function __construct(
        string $publicKey = 'MCowBQYDK2VwAyEAt6Mu4T0pBORY11W+QeM35UsmLO3vsf+6yKpFDEImFk0=',
        private ?string $expectedAudience = null
    ) {
        $this->rawPublicKey = $this->extractRawKey($publicKey);
    }

    /**
     * Verify Torob JWT token and return decoded payload.
     *
     * ponytail: native sodium replaces 3rd-party JWT lib for Ed25519/EdDSA. Upgrade path: install firebase/php-jwt if additional algorithms needed.
     *
     * @throws TorobTokenException
     */
    public function verify(string $token, ?string $requestAudience = null): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new TorobTokenException('Malformed JWT: must contain three parts');
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        $headerJson = $this->base64UrlDecode($headerB64);
        $payloadJson = $this->base64UrlDecode($payloadB64);
        $signature = $this->base64UrlDecode($signatureB64);

        $header = json_decode($headerJson, true);
        $payload = json_decode($payloadJson, true);

        if (!is_array($header) || !is_array($payload)) {
            throw new TorobTokenException('Malformed JWT: invalid JSON');
        }

        if (($header['alg'] ?? null) !== 'EdDSA') {
            throw new TorobTokenException('Unsupported algorithm: only EdDSA is accepted');
        }

        // Verify Ed25519 signature
        $message = "{$headerB64}.{$payloadB64}";
        if (strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES) {
            throw new TorobTokenException('Invalid signature length');
        }

        if (!sodium_crypto_sign_verify_detached($signature, $message, $this->rawPublicKey)) {
            throw new TorobTokenException('Invalid token signature');
        }

        $now = time();

        // Check expiration
        if (!isset($payload['exp']) || $now > $payload['exp']) {
            throw new TorobTokenException('Token has expired');
        }

        // Check not-before
        if (isset($payload['nbf']) && $now < $payload['nbf']) {
            throw new TorobTokenException('Token is not yet valid');
        }

        // Check audience
        $expectedAud = $this->expectedAudience ?? $requestAudience;
        if ($expectedAud !== null) {
            $aud = $payload['aud'] ?? null;
            if ($aud !== $expectedAud) {
                throw new TorobTokenException("Audience mismatch: expected '{$expectedAud}', got '{$aud}'");
            }
        }

        return $payload;
    }

    private function extractRawKey(string $key): string
    {
        // Strip PEM header/footer if present
        $clean = preg_replace('/-----BEGIN [A-Z ]+-----|-----END [A-Z ]+-----|\s+/', '', $key);
        $decoded = base64_decode($clean, true);

        if ($decoded === false) {
            throw new TorobTokenException('Invalid base64 encoding in public key');
        }

        // SubjectPublicKeyInfo (DER) for Ed25519 is 44 bytes; last 32 bytes are the raw public key
        if (strlen($decoded) === 44) {
            return substr($decoded, -32);
        }

        if (strlen($decoded) === 32) {
            return $decoded;
        }

        throw new TorobTokenException('Unexpected public key length for Ed25519');
    }

    public function base64UrlDecode(string $input): string
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }

        return base64_decode(strtr($input, '-_', '+/')) ?: '';
    }

    public function base64UrlEncode(string $input): string
    {
        return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
    }
}
