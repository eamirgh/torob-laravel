# Multi-Tenant Shop Generator Cookbook

Shop builders and multi-tenant platforms (e.g., Mixin or SaaS shop builders) manage Torob integrations for hundreds of individual merchant domains.

---

## 1. Tenant Shop Model with Torob Connection

Create or update your merchant/tenant model to store the connection state:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    protected $casts = [
        'torob_connected' => 'boolean',
        'torob_order_tracking_enabled' => 'boolean',
    ];
}
```

---

## 2. Dynamic Provider with Per-Tenant Control

Per Torob's specification for shop generators, each shop must explicitly control whether Torob can read its order data. If access is disabled, respond with HTTP `403`.

```php
namespace App\Services;

use App\Models\Order;
use App\Models\Shop;
use Torob\Laravel\Contracts\TorobOrderProviderContract;

class TenantTorobOrderProvider implements TorobOrderProviderContract
{
    protected Shop $shop;

    public function __construct()
    {
        // Resolve active tenant from current request domain or subdomain
        $host = request()->getHost();
        $this->shop = Shop::where('domain', $host)->firstOrFail();
    }

    public function isOrderTrackingEnabled(): bool
    {
        // Per-shop toggle: returns HTTP 403 to Torob if false
        return (bool) $this->shop->torob_order_tracking_enabled;
    }

    public function getOrders(string $purchaseTimestampGt, int $limit): array
    {
        return Order::where('shop_id', $this->shop->id)
            ->whereNotNull('torob_clid')
            ->where('created_at', '>', $purchaseTimestampGt)
            ->oldest('created_at')
            ->limit($limit)
            ->get()
            ->all();
    }
}
```

Bind in `AppServiceProvider`:

```php
$this->app->bind(
    \Torob\Laravel\Contracts\TorobOrderProviderContract::class,
    \App\Services\TenantTorobOrderProvider::class
);
```

---

## 3. Merchant Connection Handshake Controller

```php
namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;
use Torob\Laravel\Facades\Torob;

class MerchantTorobConnectionController extends Controller
{
    // Step 1: Merchant clicks "Connect Torob" in your SaaS admin panel
    public function initiate(Request $request)
    {
        /** @var Shop $shop */
        $shop = auth()->user()->shop;

        // Generate approval URL
        $connection = Torob::shopGenerator()->requestConnection(
            shopDomain: $shop->domain,
            returnUrl: route('merchant.torob.callback')
        );

        session(['torob_pending_conn' => $connection['connection_id']]);

        return redirect()->away($connection['approve_url']);
    }

    // Step 2 & 3: Torob redirects merchant back to return_url
    public function callback(Request $request)
    {
        /** @var Shop $shop */
        $shop = auth()->user()->shop;

        $callback = Torob::shopGenerator()->parseCallback($request);

        if (!$callback['approved']) {
            return redirect()->route('merchant.settings.integrations')
                ->with('error', 'Torob connection was declined by merchant.');
        }

        // Security check: ensure connection ID matches the session to avoid hijacking
        if ($callback['connection_id'] !== session('torob_pending_conn')) {
            abort(403, 'Session mismatch during connection verification.');
        }

        // Step 4: Confirm connection with Torob
        $active = Torob::shopGenerator()->confirmConnection(
            connectionId: $callback['connection_id'],
            code: $callback['code']
        );

        $shop->update([
            'torob_connection_id' => $active['connection_id'],
            'torob_connected' => true,
            'torob_order_tracking_enabled' => true,
        ]);

        return redirect()->route('merchant.settings.integrations')
            ->with('success', 'Shop successfully connected to Torob!');
    }

    // Disconnect
    public function disconnect()
    {
        /** @var Shop $shop */
        $shop = auth()->user()->shop;

        if ($shop->torob_connection_id) {
            Torob::shopGenerator()->disconnect($shop->torob_connection_id);
        }

        $shop->update([
            'torob_connected' => false,
            'torob_connection_id' => null,
            'torob_order_tracking_enabled' => false,
        ]);

        return back()->with('success', 'Disconnected from Torob.');
    }
}
```

---

## 4. Querying Shop API for a Connected Tenant

Send the connected shop's domain in any Shop API call:

```php
use Torob\Laravel\Facades\Torob;

$tenantDomain = 'merchant-shop.ir';

// Fetch merchant's performance stats from Torob
$summary = Torob::shop()->getShop(domain: $tenantDomain);

// Toggle products for that specific merchant
Torob::shop()->activateProducts(
    productIds: ['prod-1', 'prod-2'],
    domain: $tenantDomain
);

// Fetch price complaints reported on that merchant
$reports = Torob::shop()->getPriceReports(domain: $tenantDomain);
```
