<?php

namespace Torob\Laravel\Tests\Http;

use Torob\Laravel\Contracts\ProductCursorResult;
use Torob\Laravel\Contracts\ProductPageResult;
use Torob\Laravel\Contracts\TorobProductProviderContract;
use Torob\Laravel\DTO\TorobProduct;
use Torob\Laravel\Tests\TestCase;

class ProductApiTest extends TestCase
{
    private TorobProductProviderContract $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new class implements TorobProductProviderContract {
            public function getByUrls(array $urls): array
            {
                if (in_array('https://domain.com/product/34/', $urls)) {
                    return [
                        new TorobProduct(
                            pageUnique: '12412_1',
                            pageUrl: 'https://domain.com/product/34/',
                            title: 'Xiaomi Mi Note 10 Pro',
                            currentPrice: 5000000,
                            availability: true,
                            imageLinks: ['https://domain.com/images/main.jpg'],
                            dateAdded: '2024-11-22T01:15:12+03:30',
                            spec: ['memory' => '4GB']
                        ),
                    ];
                }
                return [];
            }

            public function getByUniques(array $uniques): array
            {
                if (in_array('12412_1', $uniques)) {
                    return [
                        new TorobProduct(
                            pageUnique: '12412_1',
                            pageUrl: 'https://domain.com/product/34/',
                            title: 'Xiaomi Mi Note 10 Pro',
                            currentPrice: 5000000,
                            availability: true,
                            imageLinks: ['https://domain.com/images/main.jpg'],
                            dateAdded: '2024-11-22T01:15:12+03:30',
                            spec: []
                        ),
                    ];
                }
                return [];
            }

            public function getPaginated(int $page, string $sort): ProductPageResult
            {
                $products = [
                    new TorobProduct(
                        pageUnique: '12412_1',
                        pageUrl: 'https://domain.com/product/34/',
                        title: 'Xiaomi Mi Note 10 Pro',
                        currentPrice: 5000000,
                        availability: true,
                        imageLinks: ['https://domain.com/images/main.jpg'],
                        dateAdded: '2024-11-22T01:15:12+03:30',
                        spec: []
                    ),
                ];
                return new ProductPageResult(
                    products: $products,
                    total: 150,
                    currentPage: $page,
                    maxPages: 2
                );
            }

            public function getCursor(?string $cursor, string $sort): ProductCursorResult
            {
                $products = [
                    new TorobProduct(
                        pageUnique: '12412_1',
                        pageUrl: 'https://domain.com/product/34/',
                        title: 'Xiaomi Mi Note 10 Pro',
                        currentPrice: 5000000,
                        availability: true,
                        imageLinks: ['https://domain.com/images/main.jpg'],
                        dateAdded: '2024-11-22T01:15:12+03:30',
                        spec: []
                    ),
                ];
                return new ProductCursorResult(
                    products: $products,
                    nextCursor: $cursor === null ? '12345' : null,
                    total: 100
                );
            }
        };

        $this->app->instance(TorobProductProviderContract::class, $this->provider);
    }

    public function test_rejects_empty_request_body_with_400(): void
    {
        $response = $this->withoutMiddleware()
            ->postJson('/torob_api/v3/products', []);

        $response->assertStatus(400)
            ->assertJsonStructure(['error']);
    }

    public function test_rejects_invalid_sort_parameter(): void
    {
        $response = $this->withoutMiddleware()
            ->postJson('/torob_api/v3/products', [
                'page' => 1,
                'sort' => 'invalid_sort',
            ]);

        $response->assertStatus(400)
            ->assertJson(['error' => 'Invalid sort parameter']);
    }

    public function test_rejects_missing_sort_when_page_provided(): void
    {
        $response = $this->withoutMiddleware()
            ->postJson('/torob_api/v3/products', [
                'page' => 1,
            ]);

        $response->assertStatus(400)
            ->assertJson(['error' => 'sort parameter is not provided']);
    }

    public function test_fetches_products_by_urls(): void
    {
        $response = $this->withoutMiddleware()
            ->postJson('/torob_api/v3/products', [
                'page_urls' => ['https://domain.com/product/34/'],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'api_version' => 'torob_api_v3',
                'current_page' => 1,
                'total' => 1,
                'max_pages' => 1,
                'products' => [
                    [
                        'page_unique' => '12412_1',
                        'page_url' => 'https://domain.com/product/34/',
                        'title' => 'Xiaomi Mi Note 10 Pro',
                        'current_price' => 5000000,
                        'availability' => true,
                        'image_links' => ['https://domain.com/images/main.jpg'],
                        'spec' => ['memory' => '4GB'],
                        'date_added' => '2024-11-22T01:15:12+03:30',
                    ],
                ],
            ]);
    }

    public function test_fetches_products_by_uniques(): void
    {
        $response = $this->withoutMiddleware()
            ->postJson('/torob_api/v3/products', [
                'page_uniques' => ['12412_1'],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'api_version' => 'torob_api_v3',
                'current_page' => 1,
                'total' => 1,
                'max_pages' => 1,
                'products' => [
                    [
                        'page_unique' => '12412_1',
                    ],
                ],
            ]);
    }

    public function test_fetches_products_by_page_and_sort(): void
    {
        $response = $this->withoutMiddleware()
            ->postJson('/torob_api/v3/products', [
                'page' => 2,
                'sort' => 'date_added_desc',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'api_version' => 'torob_api_v3',
                'current_page' => 2,
                'total' => 150,
                'max_pages' => 2,
            ]);
    }

    public function test_fetches_products_by_cursor_pagination(): void
    {
        $response = $this->withoutMiddleware()
            ->postJson('/torob_api/v3/products', [
                'sort' => 'product_id_desc',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'api_version' => 'torob_api_v3',
                'next_cursor' => '12345',
            ]);

        $subsequent = $this->withoutMiddleware()
            ->postJson('/torob_api/v3/products', [
                'cursor' => '12345',
                'sort' => 'product_id_desc',
            ]);

        $subsequent->assertStatus(200)
            ->assertJson([
                'api_version' => 'torob_api_v3',
                'next_cursor' => null,
            ]);
    }

    public function test_returns_empty_products_when_product_not_found(): void
    {
        $response = $this->withoutMiddleware()
            ->postJson('/torob_api/v3/products', [
                'page_uniques' => ['non_existing_unique'],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'api_version' => 'torob_api_v3',
                'current_page' => 1,
                'total' => 0,
                'max_pages' => 1,
                'products' => [],
            ]);
    }
}
