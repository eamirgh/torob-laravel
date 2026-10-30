<?php

namespace Torob\Laravel;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Torob\Laravel\Clients\TorobShopClient;
use Torob\Laravel\Clients\TorobShopGeneratorClient;
use Torob\Laravel\Clients\TorobWebhookClient;
use Torob\Laravel\Contracts\TorobActionProviderContract;
use Torob\Laravel\Contracts\TorobOrderProviderContract;
use Torob\Laravel\Contracts\TorobProductProviderContract;
use Torob\Laravel\Http\Controllers\ActionApiController;
use Torob\Laravel\Http\Controllers\OrderApiController;
use Torob\Laravel\Http\Controllers\ProductApiController;
use Torob\Laravel\Http\Middleware\CaptureTorobClid;
use Torob\Laravel\Http\Middleware\VerifyTorobToken;
use Torob\Laravel\Providers\EloquentActionProvider;
use Torob\Laravel\Providers\EloquentOrderProvider;
use Torob\Laravel\Providers\EloquentProductProvider;
use Torob\Laravel\Security\TorobTokenVerifier;

class TorobServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/torob.php', 'torob');

        $this->app->singleton(TorobTokenVerifier::class, function ($app) {
            return new TorobTokenVerifier(
                publicKey: config('torob.public_key', 'MCowBQYDK2VwAyEAt6Mu4T0pBORY11W+QeM35UsmLO3vsf+6yKpFDEImFk0='),
                expectedAudience: config('torob.audience')
            );
        });

        $this->app->singleton(TorobWebhookClient::class, function ($app) {
            return new TorobWebhookClient(
                token: config('torob.webhook_token')
            );
        });

        $this->app->singleton(TorobShopClient::class, function ($app) {
            return new TorobShopClient(
                token: config('torob.shop_token') ?? config('torob.shop_generator_token'),
                defaultDomain: config('torob.shop_domain')
            );
        });

        $this->app->singleton(TorobShopGeneratorClient::class, function ($app) {
            return new TorobShopGeneratorClient(
                token: config('torob.shop_generator_token')
            );
        });

        $this->app->singleton(TorobManager::class, function ($app) {
            return new TorobManager(
                $app->make(TorobTokenVerifier::class)
            );
        });

        $this->app->alias(TorobManager::class, 'torob');

        $this->app->bind(TorobProductProviderContract::class, function ($app) {
            $model = config('torob.models.product');
            if ($model && class_exists($model)) {
                return new EloquentProductProvider($model);
            }
            throw new BindingResolutionException(
                'No TorobProductProviderContract implementation bound. Implement TorobProductProviderContract or set torob.models.product in config.'
            );
        });

        $this->app->bind(TorobOrderProviderContract::class, function ($app) {
            $model = config('torob.models.order');
            if ($model && class_exists($model)) {
                return new EloquentOrderProvider($model, config('torob.order_tracking_enabled', true));
            }
            throw new BindingResolutionException(
                'No TorobOrderProviderContract implementation bound. Implement TorobOrderProviderContract or set torob.models.order in config.'
            );
        });

        $this->app->bind(TorobActionProviderContract::class, function ($app) {
            $model = config('torob.models.action');
            if ($model && class_exists($model)) {
                return new EloquentActionProvider($model, config('torob.action_tracking_enabled', true));
            }
            throw new BindingResolutionException(
                'No TorobActionProviderContract implementation bound. Implement TorobActionProviderContract or set torob.models.action in config.'
            );
        });
    }

    public function boot(): void
    {
        $this->registerDatabaseMacros();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/torob.php' => config_path('torob.php'),
            ], 'torob-config');

            $this->publishes([
                __DIR__ . '/../database/migrations/add_torob_clid_to_orders_table.php.stub' => database_path('migrations/' . date('Y_m_d_His') . '_add_torob_clid_to_orders_table.php'),
            ], 'torob-migrations');
        }

        $router = $this->app['router'];
        $router->aliasMiddleware('torob.auth', VerifyTorobToken::class);
        $router->aliasMiddleware('torob.clid', CaptureTorobClid::class);

        if (config('torob.routes.enabled', true)) {
            $this->registerRoutes();
        }
    }

    protected function registerDatabaseMacros(): void
    {
        if (!Blueprint::hasMacro('torobClid')) {
            Blueprint::macro('torobClid', function (string $column = 'torob_clid') {
                return $this->string($column, 255)->nullable()->index();
            });
        }

        if (!Blueprint::hasMacro('dropTorobClid')) {
            Blueprint::macro('dropTorobClid', function (string $column = 'torob_clid') {
                $this->dropIndex([$column]);
                return $this->dropColumn($column);
            });
        }
    }

    protected function registerRoutes(): void
    {
        $prefix = config('torob.routes.prefix', '');
        $middleware = config('torob.routes.middleware', ['api']);

        Route::group([
            'prefix' => $prefix,
            'middleware' => array_merge($middleware, ['torob.auth']),
        ], function () {
            Route::post('/torob_api/v3/products', ProductApiController::class)->name('torob.products');
            Route::get('/torob/v1/orders', OrderApiController::class)->name('torob.orders');
            Route::get('/torob/v1/actions', ActionApiController::class)->name('torob.actions');
        });
    }
}
