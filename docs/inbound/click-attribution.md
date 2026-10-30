# Click Attribution (`torob_clid`)

When a user clicks on a product listing on Torob, Torob redirects them to your website with a unique click ID query parameter:

```
https://yourshop.com/product/123?torob_clid=a1b2c3d4-e5f6-7890-g1h2-i3j4k5l6m7n8
```

Torob's attribution window covers purchases made within **7 days (168 hours)** of the initial click.

## Database Migration for `torob_clid`

To store `torob_clid` on your orders table, you can either publish the provided migration or use the schema blueprint macro:

### Option A: Publish Package Migration

```bash
php artisan vendor:publish --tag="torob-migrations"
php artisan migrate
```

By default, this adds an indexed nullable string column `torob_clid` to the `orders` table. You can customize the target table in `config/torob.php`:

```php
'tables' => [
    'orders' => env('TOROB_ORDERS_TABLE', 'orders'),
],
```

### Option B: Use Schema Blueprint Macro

Use the `$table->torobClid()` macro in your own migrations:

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->torobClid(); // creates indexed nullable 'torob_clid'
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropTorobClid();
        });
    }
};
```

## Capturing Click ID

Add the `CaptureTorobClid` middleware to your application's `web` middleware group in `bootstrap/app.php`:

```php
use Torob\Laravel\Http\Middleware\CaptureTorobClid;

return Application::configure(basePath: dirname(__DIR__))
    ->withMiddleware(function ($middleware) {
        $middleware->web(append: [
            CaptureTorobClid::class,
        ]);
    })
    // ...
```

The middleware automatically checks for `?torob_clid=` in the query string and stores it in an HTTP-only cookie lasting 7 days (10,080 minutes).

## Associating with Orders

When a customer completes a checkout or action, retrieve the active click ID and save it with the order:

```php
use Torob\Laravel\Facades\Torob;

// Automatically checks request query parameter, falling back to cookie
$clid = Torob::getClid($request);

$order = new Order();
$order->user_id = auth()->id();
$order->torob_clid = $clid;
$order->total = 500000;
$order->save();
```
