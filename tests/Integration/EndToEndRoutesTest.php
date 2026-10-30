<?php

namespace Torob\Laravel\Tests\Integration;

use Torob\Laravel\Contracts\ProductCursorResult;
use Torob\Laravel\Contracts\ProductPageResult;
use Torob\Laravel\Contracts\TorobActionProviderContract;
use Torob\Laravel\Contracts\TorobOrderProviderContract;
use Torob\Laravel\Contracts\TorobProductProviderContract;
use Torob\Laravel\DTO\TorobAction;
use Torob\Laravel\DTO\TorobOrder;
use Torob\Laravel\DTO\TorobProduct;
use Torob\Laravel\Tests\TestCase;

class EndToEndRoutesTest extends TestCase
{
    private string $keypair;
    private string $secretKey;

    protected function defineEnvironment($app): void
    {
        $this->keypair = sodium_crypto_sign_keypair();
        $this->secretKey = sodium_crypto_sign_secretkey($this->keypair);
        $pub = sodium_crypto_sign_publickey($this->keypair);
        $derPrefix = hex2bin('302a300506032b6570032100');
        $derBase64 = base64_encode($derPrefix . $pub);

        $app['config']->set('torob.public_key', $derBase64);
        $app['config']->set('torob.audience', 'localhost');
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Bind mock product provider
        $this->app->instance(TorobProductProviderContract::class, new class implements TorobProductProviderContract {
            public function getByUrls(array $urls): array
            {
                return [];
            }
            public function getByUniques(array $uniques): array
            {
                return [];
            }
            public function getPaginated(int $page, string $sort): ProductPageResult
            {
                return new ProductPageResult(
                    products: [
                        new TorobProduct(
                            pageUnique: 'e2e-prod-1',
                            pageUrl: 'https://example.com/p/1',
                            title: 'Sample Title',
                            currentPrice: 1000,
                            availability: true,
                            imageLinks: ['https://example.com/p1.jpg'],
                            dateAdded: '2025-01-01T00:00:00Z',
                        ),
                    ],
                    total: 1,
                    currentPage: 1,
                    maxPages: 1
                );
            }
            public function getCursor(?string $cursor, string $sort): ProductCursorResult
            {
                return new ProductCursorResult([], null, 0);
            }
        });

        // Bind mock order provider
        $this->app->instance(TorobOrderProviderContract::class, new class implements TorobOrderProviderContract {
            public function isOrderTrackingEnabled(): bool
            {
                return true;
            }
            public function getOrders(string $purchaseTimestampGt, int $limit): array
            {
                return [
                    new TorobOrder(
                        purchaseTimestamp: '2025-09-21T10:20:30Z',
                        lastUpdatedTimestamp: '2025-09-21T10:20:30Z',
                        torobClid: 'clid-123',
                        status: 'completed'
                    ),
                ];
            }
        });

        // Bind mock action provider
        $this->app->instance(TorobActionProviderContract::class, new class implements TorobActionProviderContract {
            public function isActionTrackingEnabled(): bool
            {
                return true;
            }
            public function getActions(string $timestampGt, int $limit): array
            {
                return [
                    new TorobAction(
                        timestamp: '2025-09-21T10:20:30Z',
                        lastUpdatedTimestamp: '2025-09-21T10:20:30Z',
                        torobClid: 'clid-123',
                        status: 'completed',
                        actionType: 'signup'
                    ),
                ];
            }
        });
    }

    private function generateJwt(string $aud = 'localhost'): string
    {
        $b64 = fn ($d) => rtrim(strtr(base64_encode($d), '+/', '-_'), '=');
        $h = ['alg' => 'EdDSA', 'typ' => 'JWT', 'v' => 1];
        $p = ['aud' => $aud, 'exp' => time() + 300, 'nbf' => time() - 10];

        $content = "{$b64(json_encode($h))}.{$b64(json_encode($p))}";
        $sig = sodium_crypto_sign_detached($content, $this->secretKey);

        return "{$content}.{$b64($sig)}";
    }

    public function test_e2e_products_endpoint_with_auth(): void
    {
        // Without auth -> 401
        $this->postJson('/torob_api/v3/products', [
            'page' => 1,
            'sort' => 'date_added_desc',
        ])->assertStatus(401);

        // With valid JWT -> 200
        $token = $this->generateJwt();
        $response = $this->postJson('/torob_api/v3/products', [
            'page' => 1,
            'sort' => 'date_added_desc',
        ], [
            'X-Torob-Token' => $token,
            'X-Torob-Token-Version' => '1',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'api_version' => 'torob_api_v3',
                'current_page' => 1,
                'total' => 1,
            ]);
    }

    public function test_e2e_orders_endpoint_with_auth(): void
    {
        // Without auth -> 401
        $this->getJson('/torob/v1/orders?purchase_timestamp_gt=2025-01-01T00:00:00Z&limit=10')
            ->assertStatus(401);

        // With valid JWT -> 200
        $token = $this->generateJwt();
        $response = $this->getJson('/torob/v1/orders?purchase_timestamp_gt=2025-01-01T00:00:00Z&limit=10', [
            'X-Torob-Token' => $token,
            'X-Torob-Token-Version' => '1',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    [
                        'torob_clid' => 'clid-123',
                        'status' => 'completed',
                    ],
                ],
            ]);
    }

    public function test_e2e_actions_endpoint_with_auth(): void
    {
        // Without auth -> 401
        $this->getJson('/torob/v1/actions?timestamp_gt=2025-01-01T00:00:00Z&limit=10')
            ->assertStatus(401);

        // With valid JWT -> 200
        $token = $this->generateJwt();
        $response = $this->getJson('/torob/v1/actions?timestamp_gt=2025-01-01T00:00:00Z&limit=10', [
            'X-Torob-Token' => $token,
            'X-Torob-Token-Version' => '1',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    [
                        'torob_clid' => 'clid-123',
                        'status' => 'completed',
                        'action_type' => 'signup',
                    ],
                ],
            ]);
    }
}
