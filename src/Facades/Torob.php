<?php

namespace Torob\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Torob\Laravel\TorobManager;

/**
 * @method static ?string getClid(?\Illuminate\Http\Request $request = null)
 * @method static \Torob\Laravel\Security\TorobTokenVerifier verifier()
 * @method static \Torob\Laravel\Clients\TorobWebhookClient webhook()
 * @method static \Torob\Laravel\Clients\TorobShopClient shop()
 * @method static \Torob\Laravel\Clients\TorobShopGeneratorClient shopGenerator()
 *
 * @see \Torob\Laravel\TorobManager
 */
class Torob extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return TorobManager::class;
    }
}
