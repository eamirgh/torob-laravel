# Getting Started

## Requirements

- **PHP**: `^8.2` (including 8.3 and 8.4)
- **Extensions**: `ext-sodium` and `ext-json` (built into PHP)
- **Laravel Framework**: `^10.0`, `^11.0`, `^12.0`, or `^13.0`

## Installation

Install the package into your Laravel application:

```bash
composer require eamirgh/torob-laravel
```

The package uses Laravel auto-discovery to automatically register `TorobServiceProvider` and the `Torob` facade.

## Publishing Configuration

Publish the default configuration file:

```bash
php artisan vendor:publish --tag="torob-config"
```

This creates `config/torob.php` in your application.

## Quick Setup Steps

1. Configure environment variables in `.env`.
2. Add `TorobProductContract` (and `HasTorobProduct` trait) to your `Product` model, and set `TOROB_PRODUCT_MODEL="App\Models\Product"`.
3. (Optional) Add `TorobOrderContract` (and `HasTorobOrder` trait) to your `Order` model, and set `TOROB_ORDER_MODEL="App\Models\Order"`.
4. Register the `CaptureTorobClid` middleware in your web group to track user referrals.
5. Provide your endpoints to the Torob support team.
