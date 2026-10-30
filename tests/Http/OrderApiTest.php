<?php

namespace Torob\Laravel\Tests\Http;

use Torob\Laravel\Contracts\TorobOrderProviderContract;
use Torob\Laravel\DTO\TorobOrder;
use Torob\Laravel\DTO\TorobOrderItem;
use Torob\Laravel\Tests\TestCase;

class OrderApiTest extends TestCase
{
    private TorobOrderProviderContract $provider;
    private bool $enabled = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new class ($this->enabled) implements TorobOrderProviderContract {
            public function __construct(private bool &$enabled)
            {
            }

            public function isOrderTrackingEnabled(): bool
            {
                return $this->enabled;
            }

            public function getOrders(string $purchaseTimestampGt, int $limit): array
            {
                return [
                    new TorobOrder(
                        purchaseTimestamp: '2025-09-21T10:20:30.456789Z',
                        lastUpdatedTimestamp: '2025-09-21T10:20:30.456789Z',
                        torobClid: 'a1b2c3d4-e5f6-7890-g1h2-i3j4k5l6m7n8',
                        status: 'completed',
                        psp: 'zarinpal',
                        orderValue: 500000,
                        shippingAmount: 90000,
                        phoneNumber: '+989123456789',
                        products: [
                            new TorobOrderItem(
                                productUrl: 'https://www.yourshop.com/product/789',
                                productPrice: 100000,
                                quantity: 1
                            ),
                        ]
                    ),
                ];
            }
        };

        $this->app->instance(TorobOrderProviderContract::class, $this->provider);
    }

    public function test_rejects_when_tracking_disabled_with_403(): void
    {
        $this->enabled = false;

        $response = $this->withoutMiddleware()
            ->getJson('/torob/v1/orders?purchase_timestamp_gt=2025-09-21T10:00:00.000000Z&limit=100');

        $response->assertStatus(403);
    }

    public function test_rejects_missing_purchase_timestamp_gt_with_400(): void
    {
        $response = $this->withoutMiddleware()
            ->getJson('/torob/v1/orders?limit=100');

        $response->assertStatus(400)
            ->assertJson(['error' => 'purchase_timestamp_gt parameter is required']);
    }

    public function test_rejects_invalid_limit_with_400(): void
    {
        $response = $this->withoutMiddleware()
            ->getJson('/torob/v1/orders?purchase_timestamp_gt=2025-09-21T10:00:00.000000Z&limit=0');

        $response->assertStatus(400)
            ->assertJson(['error' => 'limit must be an integer between 1 and 1000']);

        $responseTooLarge = $this->withoutMiddleware()
            ->getJson('/torob/v1/orders?purchase_timestamp_gt=2025-09-21T10:00:00.000000Z&limit=1001');

        $responseTooLarge->assertStatus(400)
            ->assertJson(['error' => 'limit must be an integer between 1 and 1000']);
    }

    public function test_fetches_orders_successfully(): void
    {
        $response = $this->withoutMiddleware()
            ->getJson('/torob/v1/orders?purchase_timestamp_gt=2025-09-21T10:00:00.000000Z&limit=100');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    [
                        'purchase_timestamp' => '2025-09-21T10:20:30.456789Z',
                        'last_updated_timestamp' => '2025-09-21T10:20:30.456789Z',
                        'torob_clid' => 'a1b2c3d4-e5f6-7890-g1h2-i3j4k5l6m7n8',
                        'status' => 'completed',
                        'psp' => 'zarinpal',
                        'order_value' => 500000,
                        'shipping_amount' => 90000,
                        'phone_number' => '+989123456789',
                        'products' => [
                            [
                                'product_url' => 'https://www.yourshop.com/product/789',
                                'product_price' => 100000,
                                'quantity' => 1,
                            ],
                        ],
                    ],
                ],
            ]);
    }

    public function test_returns_empty_data_when_no_orders(): void
    {
        $emptyProvider = new class implements TorobOrderProviderContract {
            public function isOrderTrackingEnabled(): bool
            {
                return true;
            }
            public function getOrders(string $purchaseTimestampGt, int $limit): array
            {
                return [];
            }
        };
        $this->app->instance(TorobOrderProviderContract::class, $emptyProvider);

        $response = $this->withoutMiddleware()
            ->getJson('/torob/v1/orders?purchase_timestamp_gt=2025-09-21T10:00:00.000000Z&limit=100');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [],
            ]);
    }
}
