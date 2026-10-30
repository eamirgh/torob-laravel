# Shop API Client

The Shop API client interacts with Torob's management endpoints at `https://api.torob.com/shop-api/v1/`.

## Configuration

Set your shop token in `.env`:

```bash
TOROB_SHOP_TOKEN=your_token_from_panel
TOROB_SHOP_DOMAIN=yourshop.ir
```

## Features

### 1. Shop Info & Summary
```php
use Torob\Laravel\Facades\Torob;

$shop = Torob::shop()->getShop();
// Returns shop status, product counts (available, deleted, not accessible)
```

### 2. Query Products on Torob
```php
$products = Torob::shop()->getProducts(
    filters: [
        'status' => 'page_not_available',
        'updated_since' => '2026-10-01T00:00:00Z',
    ],
    pageSize: 100
);
```

### 3. Activate & Deactivate Products
Turn products on or off in bulk (up to 5,000 product IDs):

```php
Torob::shop()->activateProducts(['12412_1', '12412_2']);
Torob::shop()->deactivateProducts(['12412_3']);
```

### 4. Click Analytics
Retrieve daily product click logs (excluding the last 6 hours):

```php
$clicks = Torob::shop()->getClicks(
    date: '2026-10-01',
    options: [
        'click_type' => 'normal',
        'page' => 1,
        'page_size' => 50,
    ]
);
```

### 5. Price Reports
View and answer reports from customers:

```php
// Fetch active reports
$reports = Torob::shop()->getPriceReports(['active_reports' => 'active']);

// Answer open reports
Torob::shop()->answerPriceReports(
    reportIds: [4521],
    status: 'claimed_to_be_corrected', // or 'claimed_to_be_invalid'
    description: 'Fixed price discrepancy'
);
```

### 6. Charge Transfer
Transfer balance between shops on Torob:

```php
// Preview available balance
$preview = Torob::shop()->getChargeTransferPreview('other-shop.ir');

// Transfer
$result = Torob::shop()->transferCharge(
    destination: 'other-shop.ir',
    amount: 50000,
    requestId: 'transfer-' . Str::uuid()
);
```
