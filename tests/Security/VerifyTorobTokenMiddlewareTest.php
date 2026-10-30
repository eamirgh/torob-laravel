<?php

namespace Torob\Laravel\Tests\Security;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Torob\Laravel\Http\Middleware\VerifyTorobToken;
use Torob\Laravel\Security\TorobTokenVerifier;
use Torob\Laravel\Tests\TestCase;

class VerifyTorobTokenMiddlewareTest extends TestCase
{
    private string $keypair;
    private string $secretKey;
    private string $publicKeyDerBase64;

    protected function setUp(): void
    {
        parent::setUp();

        $this->keypair = sodium_crypto_sign_keypair();
        $this->secretKey = sodium_crypto_sign_secretkey($this->keypair);
        $pub = sodium_crypto_sign_publickey($this->keypair);
        $derPrefix = hex2bin('302a300506032b6570032100');
        $this->publicKeyDerBase64 = base64_encode($derPrefix . $pub);
    }

    private function generateJwt(array $payload): string
    {
        $b64UrlEncode = fn ($data) => rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
        $header = ['alg' => 'EdDSA', 'typ' => 'JWT', 'v' => 1];

        $content = "{$b64UrlEncode(json_encode($header))}.{$b64UrlEncode(json_encode($payload))}";
        $sig = sodium_crypto_sign_detached($content, $this->secretKey);

        return "{$content}.{$b64UrlEncode($sig)}";
    }

    public function test_rejects_request_without_torob_token_header(): void
    {
        $verifier = new TorobTokenVerifier($this->publicKeyDerBase64);
        $middleware = new VerifyTorobToken($verifier);

        $request = Request::create('/torob_api/v3/products', 'POST');

        $response = $middleware->handle($request, function () {
            return new Response('ok');
        });

        $this->assertSame(401, $response->getStatusCode());
        $this->assertStringContainsString('Missing X-Torob-Token header', $response->getContent());
    }

    public function test_rejects_unsupported_token_version(): void
    {
        $verifier = new TorobTokenVerifier($this->publicKeyDerBase64);
        $middleware = new VerifyTorobToken($verifier);

        $request = Request::create('/torob_api/v3/products', 'POST');
        $request->headers->set('X-Torob-Token', 'some-token');
        $request->headers->set('X-Torob-Token-Version', '2');

        $response = $middleware->handle($request, function () {
            return new Response('ok');
        });

        $this->assertSame(401, $response->getStatusCode());
        $this->assertStringContainsString('Unsupported token version', $response->getContent());
    }

    public function test_allows_valid_request(): void
    {
        $verifier = new TorobTokenVerifier($this->publicKeyDerBase64);
        $middleware = new VerifyTorobToken($verifier);

        $token = $this->generateJwt([
            'aud' => 'localhost',
            'exp' => time() + 300,
            'nbf' => time() - 10,
        ]);

        $request = Request::create('http://localhost/torob_api/v3/products', 'POST');
        $request->headers->set('X-Torob-Token', $token);
        $request->headers->set('X-Torob-Token-Version', '1');

        $response = $middleware->handle($request, function ($req) {
            $this->assertNotNull($req->attributes->get('torob_token'));
            return new Response('ok');
        });

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_rejects_tampered_token(): void
    {
        $verifier = new TorobTokenVerifier($this->publicKeyDerBase64);
        $middleware = new VerifyTorobToken($verifier);

        $request = Request::create('http://localhost/torob_api/v3/products', 'POST');
        $request->headers->set('X-Torob-Token', 'invalid.token.data');
        $request->headers->set('X-Torob-Token-Version', '1');

        $response = $middleware->handle($request, function () {
            return new Response('ok');
        });

        $this->assertSame(401, $response->getStatusCode());
    }
}
