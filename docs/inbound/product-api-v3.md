# Product API v3

The Product API v3 endpoint (`POST /torob_api/v3/products`) is invoked by Torob to crawl the full catalog or refresh specific products.

## Request Modes

Torob will send **one of four** mutually exclusive payloads:

### 1. By URLs
```json
{
  "page_urls": ["https://yourshop.com/product/34/", "https://yourshop.com/product/35/"]
}
```

### 2. By Unique Identifiers
```json
{
  "page_uniques": ["12412_1", "12412_2"]
}
```

### 3. Page-Based Pagination
```json
{
  "page": 1,
  "sort": "date_added_desc"
}
```
Supported sorts: `date_added_desc`, `date_updated_desc`, or `product_id_desc`.

### 4. Cursor-Based Pagination
First request:
```json
{
  "sort": "product_id_desc"
}
```
Subsequent request:
```json
{
  "cursor": "12345",
  "sort": "product_id_desc"
}
```

## Implementation Guide

To connect your catalog, create a service implementing `TorobProductProviderContract`:

```php
namespace App\Services;

use Torob\Laravel\Contracts\TorobProductProviderContract;
use Torob\Laravel\Contracts\ProductPageResult;
use Torob\Laravel\Contracts\ProductCursorResult;
use Torob\Laravel\DTO\TorobProduct;
use App\Models\Product;

class TorobProductProvider implements TorobProductProviderContract
{
    public function getByUrls(array $urls): array
    {
        return Product::whereIn('url', $urls)
            ->get()
            ->map(fn ($p) => $this->map($p))
            ->all();
    }

    public function getByUniques(array $uniques): array
    {
        return Product::whereIn('id', $uniques)
            ->get()
            ->map(fn ($p) => $this->map($p))
            ->all();
    }

    public function getPaginated(int $page, string $sort): ProductPageResult
    {
        $query = Product::query();

        match ($sort) {
            'date_added_desc' => $query->orderBy('created_at', 'desc'),
            'date_updated_desc' => $query->orderBy('updated_at', 'desc'),
            'product_id_desc' => $query->orderBy('id', 'desc'),
        };

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
        $query = Product::orderBy('id', 'desc')->limit(100);

        if ($cursor !== null) {
            $query->where('id', '<', $cursor);
        }

        $items = $query->get();
        $nextCursor = $items->count() === 100 ? (string) $items->last()->id : null;

        return new ProductCursorResult(
            products: $items->map(fn ($p) => $this->map($p))->all(),
            nextCursor: $nextCursor
        );
    }

    protected function map(Product $p): TorobProduct
    {
        return new TorobProduct(
            pageUnique: (string) $p->id,
            pageUrl: url("/product/{$p->slug}"),
            title: $p->name,
            currentPrice: $p->is_available ? $p->price : 0,
            availability: (bool) $p->is_available,
            imageLinks: [$p->main_image_url, ...$p->gallery_urls],
            dateAdded: $p->created_at->toIso8601String(),
            spec: $p->specs ?? [],
            dateUpdated: $p->updated_at?->toIso8601String()
        );
    }
}
```

Bind in `AppServiceProvider`:

```php
$this->app->bind(
    \Torob\Laravel\Contracts\TorobProductProviderContract::class,
    \App\Services\TorobProductProvider::class
);
```

## Response Schema

The controller automatically formats the payload to match Torob API v3:

```json
{
  "api_version": "torob_api_v3",
  "current_page": 1,
  "total": 150,
  "max_pages": 2,
  "next_cursor": null,
  "products": [
    {
      "page_unique": "12412_1",
      "page_url": "https://domain.com/product/34/",
      "title": "Xiaomi Mi Note 10 Pro",
      "current_price": 5000000,
      "availability": true,
      "image_links": ["https://domain.com/images/main.jpg"],
      "spec": { "memory": "4GB" },
      "date_added": "2024-11-22T01:15:12+03:30"
    }
  ]
}
```
