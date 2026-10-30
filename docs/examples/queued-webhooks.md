# Queued Real-Time Webhooks

Torob allows up to 20 webhook requests per minute per shop, with up to 100 products per request. To keep user requests fast and avoid rate limits, always dispatch real-time update notifications via Laravel Queues.

---

## 1. The Queued Job

Create `app/Jobs/NotifyTorobProductChanged.php`:

```php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Torob\Laravel\Facades\Torob;

class NotifyTorobProductChanged implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30; // Retry after 30s on failure

    public function __construct(
        public string $pageUrl,
        public string $pageUnique
    ) {
    }

    public function handle(): void
    {
        Torob::webhook()->notifyProduct(
            pageUrl: $this->pageUrl,
            pageUnique: $this->pageUnique
        );
    }
}
```

---

## 2. Eloquent Model Observer

Create `app/Observers/ProductVariantObserver.php`:

```php
namespace App\Observers;

use App\Jobs\NotifyTorobProductChanged;
use App\Models\ProductVariant;

class ProductVariantObserver
{
    public function saved(ProductVariant $variant): void
    {
        // Only notify Torob when price, stock, or status changes
        if ($variant->wasChanged(['price_toman', 'stock', 'is_active'])) {
            NotifyTorobProductChanged::dispatch(
                $variant->getTorobPageUrl(),
                $variant->getTorobPageUnique()
            )->afterResponse();
        }
    }
}
```

Register the observer in `AppServiceProvider`:

```php
use App\Models\ProductVariant;
use App\Observers\ProductVariantObserver;

public function boot(): void
{
    ProductVariant::observe(ProductVariantObserver::class);
}
```

---

## 3. Bulk Batch Sync Command

When running imports, batch updates, or catalog recalculations, batch items in chunks of up to 100:

```php
namespace App\Console\Commands;

use App\Models\ProductVariant;
use Illuminate\Console\Command;
use Torob\Laravel\Facades\Torob;

class TorobBatchSyncCommand extends Command
{
    protected $signature = 'torob:sync-changed {--hours=1}';
    protected $description = 'Batch notify Torob of recently modified products';

    public function handle(): int
    {
        $since = now()->subHours((int) $this->option('hours'));

        $this->info("Fetching variants modified since {$since->toDateTimeString()}...");

        $variants = ProductVariant::where('updated_at', '>=', $since)->get();

        if ($variants->isEmpty()) {
            $this->info('No modified products found.');
            return 0;
        }

        $items = $variants->map(fn ($v) => [
            'page_url' => $v->getTorobPageUrl(),
            'page_unique' => $v->getTorobPageUnique(),
        ])->all();

        // TorobWebhookClient automatically partitions arrays > 100 into multiple requests
        $responses = Torob::webhook()->notify($items);

        $this->info("Successfully notified Torob about {$variants->count()} products across " . count($responses) . " chunk(s).");
        return 0;
    }
}
```
