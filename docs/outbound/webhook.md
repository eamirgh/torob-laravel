# Product Webhook Client

Instead of waiting for Torob's periodic crawlers to discover price changes or stock updates, your shop can immediately notify Torob via the Webhook API: `POST https://api.torob.com/update/webhook/v1/`.

## Setup

Ensure your webhook token is set in `.env`:

```bash
TOROB_WEBHOOK_TOKEN=your_bearer_token
```

## Usage

### Single Product Notification

```php
use Torob\Laravel\Facades\Torob;

Torob::webhook()->notifyProduct(
    pageUrl: 'https://yourshop.com/product/34/',
    pageUnique: '12412_1'
);
```

### Batch Product Notifications

Torob accepts up to 100 items per request and requires all URLs in a request to belong to the same host. The client automatically validates host consistency and partitions large arrays into 100-item chunks:

```php
use Torob\Laravel\Facades\Torob;

$items = [
    ['page_url' => 'https://yourshop.com/product/34/', 'page_unique' => '12412_1'],
    ['page_url' => 'https://yourshop.com/product/35/', 'page_unique' => '12412_2'],
];

Torob::webhook()->notify($items);
```

### Listening to Eloquent Model Events

You can trigger webhook notifications inside Eloquent Model observers or event listeners:

```php
namespace App\Observers;

use App\Models\Product;
use Torob\Laravel\Facades\Torob;

class ProductObserver
{
    public function updated(Product $product): void
    {
        if ($product->wasChanged(['price', 'is_in_stock'])) {
            Torob::webhook()->notifyProduct(
                pageUrl: url("/product/{$product->slug}"),
                pageUnique: (string) $product->id
            );
        }
    }
}
```
