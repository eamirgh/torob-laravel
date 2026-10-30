# Complete E-Commerce Store Cookbook

This recipe shows a complete, production-ready integration in a standard Laravel e-commerce store with Product variants, Order checkout with `torob_clid` attribution, and TorobPay gateway handling.

---

## 1. Product Model with Variants & Specs

In Torob, variants (different colors, sizes, warranties) share the same `product_group_id`, while having distinct `page_unique` identifiers and prices.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Torob\Laravel\Contracts\TorobProductContract;
use Torob\Laravel\Traits\HasTorobProduct;

class ProductVariant extends Model implements TorobProductContract
{
    use HasTorobProduct;

    protected $guarded = [];

    protected $casts = [
        'specs' => 'array',
        'gallery' => 'array',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Unique identifier for this variant: e.g. "105_red_64gb"
    public function getTorobPageUnique(): string
    {
        return (string) $this->sku;
    }

    // URL direct to product with variant pre-selected
    public function getTorobPageUrl(): string
    {
        return url("/product/{$this->product->slug}?variant={$this->id}");
    }

    // Title includes base title + variant attributes
    public function getTorobTitle(): string
    {
        return "{$this->product->title} {$this->color} {$this->storage}";
    }

    // Group ID connects all variants of the same product
    public function getTorobProductGroupId(): ?string
    {
        return (string) $this->product_id;
    }

    // Selling price in Tomans (use 0 if out of stock)
    public function getTorobCurrentPrice(): int
    {
        return $this->stock > 0 ? (int) $this->price_toman : 0;
    }

    public function getTorobAvailability(): bool
    {
        return $this->is_active && $this->stock > 0;
    }

    public function getTorobOldPrice(): ?int
    {
        return $this->compare_at_price_toman ? (int) $this->compare_at_price_toman : null;
    }

    // Image links: main image first
    public function getTorobImageLinks(): array
    {
        $main = $this->image_url ?? $this->product->main_image_url;
        $gallery = $this->gallery ?? [];
        return array_values(array_filter([$main, ...$gallery]));
    }

    public function getTorobSpec(): array
    {
        return array_merge($this->product->specs ?? [], [
            'color' => $this->color,
            'storage' => $this->storage,
            'warranty' => $this->warranty_title,
        ]);
    }

    public function getTorobDateAdded(): string
    {
        return $this->created_at->toIso8601String();
    }
}
```

Point the configuration to your variant model in `.env`:

```bash
TOROB_PRODUCT_MODEL="App\Models\ProductVariant"
```

---

## 2. Order & Line Item Models

Implement `TorobOrderContract` on your `Order` model and `TorobOrderItemContract` on your line item model:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Torob\Laravel\Contracts\TorobOrderContract;
use Torob\Laravel\Traits\HasTorobOrder;

class Order extends Model implements TorobOrderContract
{
    use HasTorobOrder;

    protected $guarded = [];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getTorobPurchaseTimestamp(): string
    {
        return $this->created_at->toIso8601ZuluString();
    }

    public function getTorobLastUpdatedTimestamp(): string
    {
        return $this->updated_at->toIso8601ZuluString();
    }

    public function getTorobClid(): string
    {
        return (string) $this->torob_clid;
    }

    public function getTorobStatus(): string
    {
        return in_array($this->status, ['cancelled', 'refunded'], true) ? 'cancelled' : 'completed';
    }

    // Required by Torob: must be 'torobpay' for TorobPay gateway orders
    public function getTorobPsp(): ?string
    {
        return $this->payment_gateway === 'torobpay' ? 'torobpay' : $this->payment_gateway;
    }

    public function getTorobOrderValue(): ?int
    {
        return (int) $this->items_total_toman;
    }

    public function getTorobShippingAmount(): ?int
    {
        return (int) $this->shipping_cost_toman;
    }

    public function getTorobPhoneNumber(): ?string
    {
        return $this->shipping_mobile;
    }

    public function getTorobItems(): ?array
    {
        return $this->items->all();
    }
}
```

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Torob\Laravel\Contracts\TorobOrderItemContract;
use Torob\Laravel\Traits\HasTorobOrderItem;

class OrderItem extends Model implements TorobOrderItemContract
{
    use HasTorobOrderItem;

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function getTorobProductUrl(): string
    {
        return $this->variant->getTorobPageUrl();
    }

    public function getTorobProductPrice(): int
    {
        return (int) $this->unit_price_toman;
    }

    public function getTorobQuantity(): int
    {
        return (int) $this->quantity;
    }
}
```

Register model in `.env`:

```bash
TOROB_ORDER_MODEL="App\Models\Order"
```

---

## 3. Checkout Controller

Capture the visitor's `torob_clid` and attach it to the created order:

```php
namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Torob\Laravel\Facades\Torob;

class CheckoutController extends Controller
{
    public function process(Request $request)
    {
        $validated = $request->validate([
            'address' => 'required|string',
            'mobile' => 'required|string',
            'gateway' => 'required|string',
        ]);

        $order = new Order();
        $order->user_id = auth()->id();
        $order->shipping_mobile = $validated['mobile'];
        $order->payment_gateway = $validated['gateway'];

        // Automatically captures click ID from active request or 7-day cookie:
        $order->torob_clid = Torob::getClid($request);

        $order->save();

        // Attach line items...
        return redirect()->route('payment.redirect', $order);
    }
}
```
