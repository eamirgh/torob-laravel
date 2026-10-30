<?php

namespace Torob\Laravel;

use Illuminate\Http\Request;
use Torob\Laravel\Clients\TorobShopClient;
use Torob\Laravel\Clients\TorobShopGeneratorClient;
use Torob\Laravel\Clients\TorobWebhookClient;
use Torob\Laravel\Http\Middleware\CaptureTorobClid;
use Torob\Laravel\Security\TorobTokenVerifier;

class TorobManager
{
    protected ?TorobWebhookClient $webhookClient = null;
    protected ?TorobShopClient $shopClient = null;
    protected ?TorobShopGeneratorClient $shopGeneratorClient = null;

    public function __construct(
        protected TorobTokenVerifier $verifier
    ) {
    }

    /**
     * Retrieve the Torob click ID (torob_clid) from query parameter or cookie.
     */
    public function getClid(?Request $request = null): ?string
    {
        $request ??= request();
        if (!$request) {
            return null;
        }

        $queryClid = $request->query(CaptureTorobClid::COOKIE_NAME);
        if ($queryClid !== null && is_string($queryClid) && $queryClid !== '') {
            return $queryClid;
        }

        $cookieClid = $request->cookie(CaptureTorobClid::COOKIE_NAME);
        if ($cookieClid !== null && is_string($cookieClid) && $cookieClid !== '') {
            return $cookieClid;
        }

        return null;
    }

    public function verifier(): TorobTokenVerifier
    {
        return $this->verifier;
    }

    public function webhook(?string $token = null): TorobWebhookClient
    {
        if ($token !== null) {
            return new TorobWebhookClient(token: $token);
        }

        return $this->webhookClient ??= new TorobWebhookClient();
    }

    public function shop(?string $token = null, ?string $domain = null): TorobShopClient
    {
        if ($token !== null || $domain !== null) {
            return new TorobShopClient(token: $token, defaultDomain: $domain);
        }

        return $this->shopClient ??= new TorobShopClient();
    }

    public function shopGenerator(?string $token = null): TorobShopGeneratorClient
    {
        if ($token !== null) {
            return new TorobShopGeneratorClient(token: $token);
        }

        return $this->shopGeneratorClient ??= new TorobShopGeneratorClient();
    }
}
