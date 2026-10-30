# Order Tracking API

The Order Tracking API endpoint (`GET /torob/v1/orders`) provides Torob with sales data attributed to referrals from Torob.

## Query Parameters

Torob polls your endpoint periodically:

| Parameter | Type | Required | Description |
|---|---|---|---|
| `purchase_timestamp_gt` | String | Yes | ISO 8601 UTC timestamp (`2025-09-21T10:00:00.000000Z`). Returns orders created after this time. |
| `limit` | Integer | Yes | Maximum orders to return (1 to 1000). |

## Implementation

Implement `TorobOrderProviderContract`:

```php
namespace App\Services;

use Torob\Laravel\Contracts\TorobOrderProviderContract;
use Torob\Laravel\DTO\TorobOrder;
use Torob\Laravel\DTO\TorobOrderItem;
use App\Models\Order;

class TorobOrderProvider implements TorobOrderProviderContract
{
    public function isOrderTrackingEnabled(): bool
    {
        // For shop generators: check if this store has enabled Torob tracking
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
                    status: $order->is_cancelled ? 'cancelled' : 'completed',
                    psp: $order->gateway_name, // 'torobpay' for TorobPay orders
                    orderValue: $order->subtotal_toman,
                    shippingAmount: $order->shipping_cost_toman,
                    phoneNumber: $order->customer_mobile,
                    products: $order->items->map(fn ($item) => new TorobOrderItem(
                        productUrl: url("/product/{$item->product->slug}"),
                        productPrice: $item->price_toman,
                        quantity: $item->qty
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
    \App\Services\TorobOrderProvider::class
);
```

## Order Updates & Cancellations

Torob polls for updates up to 7 days after the initial purchase. When an order is cancelled:
1. Update order status to `'cancelled'`.
2. Update `lastUpdatedTimestamp`.
3. The next polling query will pick up the updated record.
