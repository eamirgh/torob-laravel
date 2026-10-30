# Action Tracking API

The Action Tracking API endpoint (`GET /torob/v1/actions`) tracks non-order user conversions (e.g., newsletter signups, membership registrations, RFQ submissions) referred by Torob.

## Query Parameters

| Parameter | Type | Required | Description |
|---|---|---|---|
| `timestamp_gt` | String | Yes | ISO 8601 UTC timestamp. Returns actions completed after this time. |
| `limit` | Integer | Yes | Maximum actions to return (1 to 1000). |

## Implementation

Implement `TorobActionProviderContract`:

```php
namespace App\Services;

use Torob\Laravel\Contracts\TorobActionProviderContract;
use Torob\Laravel\DTO\TorobAction;
use App\Models\UserAction;

class TorobActionProvider implements TorobActionProviderContract
{
    public function isActionTrackingEnabled(): bool
    {
        return true;
    }

    public function getActions(string $timestampGt, int $limit): array
    {
        return UserAction::whereNotNull('torob_clid')
            ->where('created_at', '>', $timestampGt)
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get()
            ->map(function ($action) {
                return new TorobAction(
                    timestamp: $action->created_at->toIso8601ZuluString(),
                    lastUpdatedTimestamp: $action->updated_at->toIso8601ZuluString(),
                    torobClid: $action->torob_clid,
                    status: $action->is_cancelled ? 'cancelled' : 'completed',
                    actionType: $action->type
                );
            })
            ->all();
    }
}
```

Bind in `AppServiceProvider`:

```php
$this->app->bind(
    \Torob\Laravel\Contracts\TorobActionProviderContract::class,
    \App\Services\TorobActionProvider::class
);
```
