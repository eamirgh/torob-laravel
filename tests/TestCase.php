<?php

namespace Torob\Laravel\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Torob\Laravel\TorobServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            TorobServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('torob.public_key', 'MCowBQYDK2VwAyEAt6Mu4T0pBORY11W+QeM35UsmLO3vsf+6yKpFDEImFk0=');
        $app['config']->set('torob.audience', 'localhost');
    }
}
