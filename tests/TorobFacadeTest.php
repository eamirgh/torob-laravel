<?php

namespace Torob\Laravel\Tests;

use Torob\Laravel\Clients\TorobShopClient;
use Torob\Laravel\Clients\TorobShopGeneratorClient;
use Torob\Laravel\Clients\TorobWebhookClient;
use Torob\Laravel\Facades\Torob;
use Torob\Laravel\Security\TorobTokenVerifier;

class TorobFacadeTest extends TestCase
{
    public function test_facade_resolves_services(): void
    {
        $this->assertInstanceOf(TorobTokenVerifier::class, Torob::verifier());
        $this->assertInstanceOf(TorobWebhookClient::class, Torob::webhook());
        $this->assertInstanceOf(TorobShopClient::class, Torob::shop());
        $this->assertInstanceOf(TorobShopGeneratorClient::class, Torob::shopGenerator());
    }
}
