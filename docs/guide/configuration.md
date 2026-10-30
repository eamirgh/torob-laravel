# Configuration

All configuration settings are defined in `config/torob.php` and can be overridden via environment variables.

## Environment Variables

```bash
# -----------------------------------------------------------------------------
# Inbound Security (Torob -> Shop)
# -----------------------------------------------------------------------------
# Base64-encoded DER public key provided by Torob (Ed25519)
TOROB_PUBLIC_KEY="MCowBQYDK2VwAyEAt6Mu4T0pBORY11W+QeM35UsmLO3vsf+6yKpFDEImFk0="

# Expected JWT 'aud' claim. If null, automatically matches the incoming Host header
TOROB_AUDIENCE="api.yourshop.com"

# -----------------------------------------------------------------------------
# Routes Setup
# -----------------------------------------------------------------------------
# Enable/disable package-provided routes
TOROB_ROUTES_ENABLED=true

# Optional route prefix (e.g., 'api' or 'integrations')
TOROB_ROUTES_PREFIX=""

# -----------------------------------------------------------------------------
# Feature Toggles
# -----------------------------------------------------------------------------
# Allows or blocks Torob from reading order/action data (403 when disabled)
TOROB_ORDER_TRACKING_ENABLED=true
TOROB_ACTION_TRACKING_ENABLED=true

# -----------------------------------------------------------------------------
# Outbound Credentials (Shop -> Torob)
# -----------------------------------------------------------------------------
# Token for calling POST https://api.torob.com/update/webhook/v1/
TOROB_WEBHOOK_TOKEN="your_webhook_token"

# Token for calling Shop API (obtained from panel.torob.com)
TOROB_SHOP_TOKEN="your_shop_token"

# Partner token for shop builders/generators
TOROB_SHOP_GENERATOR_TOKEN="your_generator_token"

# Default domain for Shop API requests (e.g. example.ir)
TOROB_SHOP_DOMAIN="example.ir"
```

## Route Endpoints

When `TOROB_ROUTES_ENABLED=true`, the package registers:

| Endpoint | Method | Middleware | Description |
|---|---|---|---|
| `/torob_api/v3/products` | `POST` | `torob.auth` | Product API v3 catalog endpoint |
| `/torob/v1/orders` | `GET` | `torob.auth` | Order tracking endpoint |
| `/torob/v1/actions` | `GET` | `torob.auth` | Action tracking endpoint |

If you specify `TOROB_ROUTES_PREFIX="api"`, endpoints become `/api/torob_api/v3/products`, `/api/torob/v1/orders`, etc.
