# Shop Generator Connection

Shop generators (multi-tenant SaaS e-commerce platforms like Mixin, WooCommerce multi-shop platforms, etc.) call the Shop API on behalf of connected shops.

Before accessing a merchant's shop, the merchant must authorize the connection.

## Connection Lifecycle

1. **Request Connection**: Platform initiates a connection request to Torob.
2. **Merchant Approval**: Merchant is redirected to Torob to approve access.
3. **Approval Callback**: Torob redirects back to your platform with an authorization code.
4. **Confirm Connection**: Platform exchanges the code to activate the connection.

```
Merchant               Your Platform (Generator)                   Torob
   │                               │                                 │
   │── Click "Connect Torob" ─────>│                                 │
   │                               │── POST /connections/ ──────────>│
   │                               │<─ Return approve_url ───────────│
   │<─ Redirect to approve_url ────│                                 │
   │                                                                 │
   │── Approve Access on Torob ─────────────────────────────────────>│
   │<─ Redirect to return_url?code=XYZ&connection_id=UUID ───────────│
   │                                                                 │
   │── Hit return_url ────────────>│                                 │
   │                               │── POST /connections/{id}/confirm│
   │                               │   with code=XYZ ───────────────>│
   │                               │<─ status: active ───────────────│
```

## Controller Example

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Torob\Laravel\Facades\Torob;

class TorobConnectionController
{
    // Step 1: Start connection
    public function connect(Request $request)
    {
        $shopDomain = $request->input('domain'); // e.g. 'merchant.ir'
        $returnUrl = route('torob.callback');

        $connection = Torob::shopGenerator()->requestConnection($shopDomain, $returnUrl);

        session(['torob_conn_id' => $connection['connection_id']]);

        return redirect()->away($connection['approve_url']);
    }

    // Step 2 & 3: Handle callback
    public function callback(Request $request)
    {
        $result = Torob::shopGenerator()->parseCallback($request);

        if (!$result['approved']) {
            return redirect()->route('dashboard')->with('error', 'Merchant denied access');
        }

        // Verify connection_id matches session to prevent CSRF / domain hijacking
        if ($result['connection_id'] !== session('torob_conn_id')) {
            abort(403, 'Connection ID mismatch');
        }

        // Step 4: Confirm connection
        $activeConnection = Torob::shopGenerator()->confirmConnection(
            $result['connection_id'],
            $result['code']
        );

        // Save connection status in database
        // auth()->user()->shop->update(['torob_connected' => true]);

        return redirect()->route('dashboard')->with('success', 'Connected to Torob!');
    }

    // Disconnect
    public function disconnect(string $connectionId)
    {
        Torob::shopGenerator()->disconnect($connectionId);
        return back()->with('success', 'Disconnected from Torob');
    }
}
```
