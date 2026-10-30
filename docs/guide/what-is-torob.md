# What is Torob Sync?

Torob (ترب) is the leading Iranian price comparison search engine and e-commerce aggregator. For large e-commerce platforms and shop generator platforms (shop builders), direct API synchronization is required to maintain real-time prices, stock availability, and sales attribution.

## Integration Architecture

The integration between Torob and your Laravel shop works in two opposite directions:

```
┌─────────────────┐                      ┌─────────────────┐
│                 │── POST /products ───>│                 │
│                 │── GET  /orders ─────>│   Your Laravel  │
│      Torob      │── GET  /actions ────>│   Application   │
│                 │                      │                 │
│                 │<─ POST /webhook ─────│  (torob-laravel)│
│                 │<─ REST /shop-api ────│                 │
└─────────────────┘                      └─────────────────┘
```

### 1. Inbound (Torob → Your Shop)
Torob's servers poll your endpoints to fetch product listings, check stock/price, and retrieve orders and conversion actions referred by Torob.
- **Product API v3**: Product catalog crawl and single-product refresh.
- **Order Tracking API v1**: Polling orders that carry a valid `torob_clid`.
- **Action Tracking API v1**: Polling custom user actions (signups, leads, etc.) with `torob_clid`.

All inbound requests are authenticated with an **Ed25519 (EdDSA)** asymmetric JWT token signed by Torob.

### 2. Outbound (Your Shop → Torob)
Your Laravel server calls Torob's APIs to trigger real-time updates and automate shop panel operations:
- **Product Update Webhook**: Notify Torob immediately when an item's price or stock changes, avoiding crawl delays.
- **Shop API**: Fetch performance metrics, product statuses, click logs, answer customer price reports, and manage balances.
- **Shop Generator Connection API**: Multi-tenant handshake allowing shop builders to connect merchant stores.
