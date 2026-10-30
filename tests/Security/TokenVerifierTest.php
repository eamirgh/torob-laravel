<?php

namespace Torob\Laravel\Tests\Security;

use Torob\Laravel\Exceptions\TorobTokenException;
use Torob\Laravel\Security\TorobTokenVerifier;
use Torob\Laravel\Tests\TestCase;

class TokenVerifierTest extends TestCase
{
    private string $keypair;
    private string $secretKey;
    private string $publicKey;
    private string $publicKeyDerBase64;

    protected function setUp(): void
    {
        parent::setUp();

        $this->keypair = sodium_crypto_sign_keypair();
        $this->secretKey = sodium_crypto_sign_secretkey($this->keypair);
        $this->publicKey = sodium_crypto_sign_publickey($this->keypair);

        // Ed25519 SubjectPublicKeyInfo prefix in DER: 302a300506032b6570032100 + 32-byte pubkey
        $derPrefix = hex2bin('302a300506032b6570032100');
        $this->publicKeyDerBase64 = base64_encode($derPrefix . $this->publicKey);
    }

    private function generateJwt(array $payload, ?string $secretKey = null, array $header = ['alg' => 'EdDSA', 'typ' => 'JWT', 'v' => 1]): string
    {
        $b64UrlEncode = fn ($data) => rtrim(strtr(base64_encode($data), '+/', '-_'), '=');

        $headerEncoded = $b64UrlEncode(json_encode($header));
        $payloadEncoded = $b64UrlEncode(json_encode($payload));
        $content = "{$headerEncoded}.{$payloadEncoded}";

        $sig = sodium_crypto_sign_detached($content, $secretKey ?? $this->secretKey);
        $sigEncoded = $b64UrlEncode($sig);

        return "{$content}.{$sigEncoded}";
    }

    public function test_verifies_valid_jwt_token(): void
    {
        $verifier = new TorobTokenVerifier(
            publicKey: $this->publicKeyDerBase64,
            expectedAudience: 'example.com'
        );

        $token = $this->generateJwt([
            'aud' => 'example.com',
            'exp' => time() + 300,
            'nbf' => time() - 10,
        ]);

        $payload = $verifier->verify($token, 'example.com');

        $this->assertSame('example.com', $payload['aud']);
    }

    public function test_rejects_expired_token(): void
    {
        $verifier = new TorobTokenVerifier($this->publicKeyDerBase64, 'example.com');

        $token = $this->generateJwt([
            'aud' => 'example.com',
            'exp' => time() - 10,
            'nbf' => time() - 300,
        ]);

        $this->expectException(TorobTokenException::class);
        $this->expectExceptionMessage('Token has expired');
        $verifier->verify($token, 'example.com');
    }

    public function test_rejects_premature_token_before_nbf(): void
    {
        $verifier = new TorobTokenVerifier($this->publicKeyDerBase64, 'example.com');

        $token = $this->generateJwt([
            'aud' => 'example.com',
            'exp' => time() + 300,
            'nbf' => time() + 100,
        ]);

        $this->expectException(TorobTokenException::class);
        $this->expectExceptionMessage('Token is not yet valid');
        $verifier->verify($token, 'example.com');
    }

    public function test_rejects_invalid_audience(): void
    {
        $verifier = new TorobTokenVerifier($this->publicKeyDerBase64, 'example.com');

        $token = $this->generateJwt([
            'aud' => 'other-site.com',
            'exp' => time() + 300,
            'nbf' => time() - 10,
        ]);

        $this->expectException(TorobTokenException::class);
        $this->expectExceptionMessage('Audience mismatch');
        $verifier->verify($token, 'example.com');
    }

    public function test_rejects_invalid_signature(): void
    {
        $verifier = new TorobTokenVerifier($this->publicKeyDerBase64, 'example.com');

        // Sign with different key
        $anotherKey = sodium_crypto_sign_secretkey(sodium_crypto_sign_keypair());
        $token = $this->generateJwt([
            'aud' => 'example.com',
            'exp' => time() + 300,
            'nbf' => time() - 10,
        ], $anotherKey);

        $this->expectException(TorobTokenException::class);
        $this->expectExceptionMessage('Invalid token signature');
        $verifier->verify($token, 'example.com');
    }

    public function test_rejects_malformed_token(): void
    {
        $verifier = new TorobTokenVerifier($this->publicKeyDerBase64, 'example.com');

        $this->expectException(TorobTokenException::class);
        $verifier->verify('not.a.valid.jwt.format', 'example.com');
    }
}
