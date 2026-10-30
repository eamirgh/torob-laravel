# Laravel Torob Integration Package

[![Latest Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://packagist.org/packages/eamirgh/torob-laravel)
[![Tests](https://github.com/eamirgh/torob-laravel/actions/workflows/tests.yml/badge.svg)](https://github.com/eamirgh/torob-laravel/actions)
[![Documentation](https://img.shields.io/badge/docs-GitHub_Pages-blue.svg)](https://eamirgh.github.io/torob-laravel/)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

A Laravel package for integrating with **Torob's Product and Order Sync systems**, including:
- **Product API v3**: Inbound product synchronization endpoint with JWT EdDSA authentication (page & cursor pagination).
- **Order Tracking API v1**: Inbound order attribution endpoint with JWT validation.
- **Action Tracking API v1**: Inbound action attribution endpoint.
- **Click ID (`torob_clid`) Capture**: Middleware and helpers to store and retrieve `torob_clid` across a 7-day attribution window.
- **Product Update Webhook**: Outbound notification client for instant product updates.
- **Shop API**: Outbound client to manage shop products, activation, clicks, price reports, and charge transfers.
- **Shop Generator Connection API**: Outbound client for platforms connecting merchant shops to Torob.

---

## Requirements

- PHP `^8.2` (including PHP 8.3 & 8.4)
- Extensions: `ext-sodium`, `ext-json` (built into PHP)
- Laravel `^11.0`, `^12.0`, or `^13.0`

---

## Installation

Install the package via Composer:

```bash
composer require eamirgh/torob-laravel
```

Publish configuration and database migration:

```bash
php artisan vendor:publish --tag="torob-config"
php artisan vendor:publish --tag="torob-migrations"
php artisan migrate
```

---

## Configuration

In `config/torob.php` (or your `.env` file):

```env
# Torob Public Key for verifying inbound JWTs (Ed25519)
TOROB_PUBLIC_KEY=MCowBQYDK2VwAyEAt6Mu4T0pBORY11W+QeM35UsmLO3vsf+6yKpFDEImFk0=

# Expected audience claim in JWT (defaults to request host)
TOROB_AUDIENCE=your-domain.com

# Feature toggles
TOROB_ORDER_TRACKING_ENABLED=true
TOROB_ACTION_TRACKING_ENABLED=true

# Model mappings for automatic Eloquent provider binding (optional)
TOROB_PRODUCT_MODEL="App\Models\Product"
TOROB_ORDER_MODEL="App\Models\Order"
TOROB_ACTION_MODEL="App\Models\UserAction"

# Outbound tokens
TOROB_WEBHOOK_TOKEN=your_webhook_token
TOROB_SHOP_TOKEN=your_shop_token
TOROB_SHOP_GENERATOR_TOKEN=your_partner_token
TOROB_SHOP_DOMAIN=yourshop.ir
```

---

## Model Interface Integration

The cleanest way to integrate is having your Eloquent models implement the package interfaces. The package provides traits with sensible defaults.

### Product Model (`TorobProductContract`)

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Torob\Laravel\Contracts\TorobProductContract;
use Torob\Laravel\Traits\HasTorobProduct;

class Product extends Model implements TorobProductContract
{
    use HasTorobProduct;

    // Optional: override any getter to customize mapping
    // public function getTorobCurrentPrice(): int { ... }
}
```

Then in `.env`:
```env
TOROB_PRODUCT_MODEL="App\Models\Product"
```
The package automatically binds the built-in `EloquentProductProvider` — no custom provider code needed!

### Order Model (`TorobOrderContract`)

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Torob\Laravel\Contracts\TorobOrderContract;
use Torob\Laravel\Traits\HasTorobOrder;

class Order extends Model implements TorobOrderContract
{
    use HasTorobOrder;
}
```

In `.env`:
```env
TOROB_ORDER_MODEL="App\Models\Order"
```

---

## Custom Providers (Advanced)

If you need custom query logic instead of the built-in Eloquent providers, implement the provider contracts directly:

### 1. Implement Product Provider

Create a class implementing `Torob\Laravel\Contracts\TorobProductProviderContract`:

```php
namespace App\Services\Torob;

use Torob\Laravel\Contracts\TorobProductProviderContract;
use Torob\Laravel\Contracts\ProductPageResult;
use Torob\Laravel\Contracts\ProductCursorResult;
use Torob\Laravel\DTO\TorobProduct;
use App\Models\Product;

class TorobProductProvider implements TorobProductProviderContract
{
    public function getByUrls(array $urls): array
    {
        return Product::whereIn('url', $urls)->get()->map(fn ($p) => $this->map($p))->all();
    }

    public function getByUniques(array $uniques): array
    {
        return Product::whereIn('id', $uniques)->get()->map(fn ($p) => $this->map($p))->all();
    }

    public function getPaginated(int $page, string $sort): ProductPageResult
    {
        $query = Product::query();
        // apply sort: date_added_desc, date_updated_desc, product_id_desc
        $paginator = $query->paginate(100, ['*'], 'page', $page);

        return new ProductPageResult(
            products: $paginator->getCollection()->map(fn ($p) => $this->map($p))->all(),
            total: $paginator->total(),
            currentPage: $paginator->currentPage(),
            maxPages: $paginator->lastPage()
        );
    }

    public function getCursor(?string $cursor, string $sort): ProductCursorResult
    {
        // Cursor pagination with product_id_desc
        $products = Product::where('id', '<', $cursor ?? PHP_INT_MAX)
            ->orderBy('id', 'desc')
            ->limit(100)
            ->get();

        $nextCursor = $products->count() === 100 ? (string) $products->last()->id : null;

        return new ProductCursorResult(
            products: $products->map(fn ($p) => $this->map($p))->all(),
            nextCursor: $nextCursor
        );
    }

    private function map(Product $p): TorobProduct
    {
        return new TorobProduct(
            pageUnique: (string) $p->id,
            pageUrl: url("/products/{$p->slug}"),
            title: $p->name,
            currentPrice: $p->price_toman,
            availability: $p->is_in_stock,
            imageLinks: [$p->image_url],
            dateAdded: $p->created_at->toIso8601String(),
            spec: $p->specs ?? []
        );
    }
}
```

Bind it in your `AppServiceProvider`:

```php
use Torob\Laravel\Contracts\TorobProductProviderContract;
use App\Services\Torob\TorobProductProvider;

public function register(): void
{
    $this->app->bind(TorobProductProviderContract::class, TorobProductProvider::class);
}
```

---

### 2. Implement Order Tracking Provider

```php
namespace App\Services\Torob;

use Torob\Laravel\Contracts\TorobOrderProviderContract;
use Torob\Laravel\DTO\TorobOrder;
use Torob\Laravel\DTO\TorobOrderItem;
use App\Models\Order;

class TorobOrderProvider implements TorobOrderProviderContract
{
    public function isOrderTrackingEnabled(): bool
    {
        return true;
    }

    public function getOrders(string $purchaseTimestampGt, int $limit): array
    {
        return Order::whereNotNull('torob_clid')
            ->where('created_at', '>', $purchaseTimestampGt)
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get()
            ->map(function ($order) {
                return new TorobOrder(
                    purchaseTimestamp: $order->created_at->toIso8601ZuluString(),
                    lastUpdatedTimestamp: $order->updated_at->toIso8601ZuluString(),
                    torobClid: $order->torob_clid,
                    status: $order->status === 'cancelled' ? 'cancelled' : 'completed',
                    orderValue: $order->total_toman,
                    products: $order->items->map(fn ($item) => new TorobOrderItem(
                        productUrl: url("/products/{$item->product->slug}"),
                        productPrice: $item->unit_price_toman,
                        quantity: $item->quantity
                    ))->all()
                );
            })
            ->all();
    }
}
```

Bind in `AppServiceProvider`:

```php
$this->app->bind(
    \Torob\Laravel\Contracts\TorobOrderProviderContract::class,
    \App\Services\Torob\TorobOrderProvider::class
);
```

---

### 3. Click ID Attribution (`torob_clid`) & Database Migration

Add `torob_clid` to your orders table using either:

**Option A: Publish and run the package migration:**
```bash
php artisan vendor:publish --tag="torob-migrations"
php artisan migrate
```
*(Configurable target table in `config/torob.php`: `'tables.orders' => 'orders'`)*

**Option B: Use the Schema Blueprint macro in any custom migration:**
```php
Schema::table('orders', function (Blueprint $table) {
    $table->torobClid(); // creates indexed nullable 'torob_clid' column
});
```

Add the middleware to your `web` middleware group in `bootstrap/app.php`:

```php
$middleware->web(append: [
    \Torob\Laravel\Http\Middleware\CaptureTorobClid::class,
]);
```

When an order is created, save the `torob_clid`:

```php
use Torob\Laravel\Facades\Torob;

$order->torob_clid = Torob::getClid($request);
$order->save();
```

---

## Outbound API Clients

### Product Webhook Client

Notify Torob immediately when a product changes:

```php
use Torob\Laravel\Facades\Torob;

// Single product
Torob::webhook()->notifyProduct(
    pageUrl: 'https://domain.com/product/34/',
    pageUnique: '12412_1'
);

// Batch of products
Torob::webhook()->notify([
    ['page_url' => 'https://domain.com/product/34/', 'page_unique' => '12412_1'],
    ['page_url' => 'https://domain.com/product/35/', 'page_unique' => '12412_2'],
]);
```

### Shop API Client

```php
use Torob\Laravel\Facades\Torob;

// Get shop summary
$shop = Torob::shop()->getShop();

// Toggle products
Torob::shop()->activateProducts(['12412_1', '12412_2']);
Torob::shop()->deactivateProducts(['12412_3']);

// Fetch clicks
$clicks = Torob::shop()->getClicks(date: '2026-10-01');

// Fetch price reports and answer
$reports = Torob::shop()->getPriceReports();
Torob::shop()->answerPriceReports([4521], 'claimed_to_be_corrected', 'Price updated');

// Transfer balance
$preview = Torob::shop()->getChargeTransferPreview('other.ir');
$transfer = Torob::shop()->transferCharge('other.ir', 10000, 'req-uuid');
```

### Shop Generator Connection API

For shop builders / multi-tenant platforms:

```php
use Torob\Laravel\Facades\Torob;

// 1. Request connection
$conn = Torob::shopGenerator()->requestConnection('merchant.ir', 'https://builder.ir/torob/callback');
return redirect($conn['approve_url']);

// 2. Handle callback
$callback = Torob::shopGenerator()->parseCallback($request);
if ($callback['approved']) {
    Torob::shopGenerator()->confirmConnection($callback['connection_id'], $callback['code']);
}

// 3. Disconnect
Torob::shopGenerator()->disconnect($connectionId);
```

---

## Documentation & Recipes

Full documentation and interactive recipes are hosted on GitHub Pages:
**[https://eamirgh.github.io/torob-laravel/](https://eamirgh.github.io/torob-laravel/)**

- **[Complete E-Commerce Store Recipe](https://eamirgh.github.io/torob-laravel/examples/e-commerce-cookbook.html)**: Real-world ProductVariant model with specs, Order checkout with `torob_clid`, and TorobPay gateway handling.
- **[Queued Real-Time Webhooks](https://eamirgh.github.io/torob-laravel/examples/queued-webhooks.html)**: Eloquent Observers + Laravel Queue Jobs for rate-limited product change notifications.
- **[Multi-Tenant Shop Generator Platform](https://eamirgh.github.io/torob-laravel/examples/shop-generator-cookbook.html)**: Dynamic tenant routing, per-merchant tracking toggles, and multi-tenant Shop API calls.

Run docs locally:

```bash
npm run docs:dev
```

Build static documentation:

```bash
npm run docs:build
```

---

## Testing

Run tests with PHPUnit:

```bash
composer test
```
