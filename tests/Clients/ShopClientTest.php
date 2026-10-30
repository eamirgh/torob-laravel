<?php

namespace Torob\Laravel\Tests\Clients;

use Illuminate\Support\Facades\Http;
use Torob\Laravel\Clients\TorobShopClient;
use Torob\Laravel\Exceptions\TorobApiException;
use Torob\Laravel\Tests\TestCase;

class ShopClientTest extends TestCase
{
    private TorobShopClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new TorobShopClient(
            token: 'shop-test-token',
            defaultDomain: 'example.ir'
        );
    }

    public function test_get_shop_details(): void
    {
        Http::fake([
            'https://api.torob.com/shop-api/v1/shop/?domain=example.ir' => Http::response([
                'domain' => 'example.ir',
                'name' => 'فروشگاه نمونه',
                'shop_status' => 'active',
                'product_summary' => [
                    'total' => 900,
                    'available' => 600,
                ],
            ], 200),
        ]);

        $data = $this->client->getShop();

        $this->assertSame('example.ir', $data['domain']);
        $this->assertSame('active', $data['shop_status']);
    }

    public function test_get_products(): void
    {
        Http::fake([
            'https://api.torob.com/shop-api/v1/products/*' => Http::response([
                'previous' => null,
                'next' => null,
                'results' => [
                    [
                        'product_id' => '12412_1',
                        'price' => 1250000,
                        'availability' => true,
                    ],
                ],
            ], 200),
        ]);

        $data = $this->client->getProducts(filters: ['status' => 'page_not_available'], pageSize: 50);

        $this->assertCount(1, $data['results']);
        $this->assertSame('12412_1', $data['results'][0]['product_id']);
    }

    public function test_activate_products(): void
    {
        Http::fake([
            'https://api.torob.com/shop-api/v1/products/activate/?domain=example.ir' => Http::response([
                'results' => [
                    ['product_id' => '12412_1', 'found' => true],
                ],
            ], 200),
        ]);

        $res = $this->client->activateProducts(['12412_1']);
        $this->assertTrue($res['results'][0]['found']);
    }

    public function test_deactivate_products(): void
    {
        Http::fake([
            'https://api.torob.com/shop-api/v1/products/deactivate/?domain=example.ir' => Http::response([
                'results' => [
                    ['product_id' => '12412_1', 'found' => true],
                ],
            ], 200),
        ]);

        $res = $this->client->deactivateProducts(['12412_1']);
        $this->assertTrue($res['results'][0]['found']);
    }

    public function test_get_clicks(): void
    {
        Http::fake([
            'https://api.torob.com/shop-api/v1/clicks/*' => Http::response([
                'count' => 1,
                'results' => [
                    [
                        'product_id' => '12412_1',
                        'click_price' => 500,
                    ],
                ],
            ], 200),
        ]);

        $data = $this->client->getClicks(date: '2026-10-01');
        $this->assertSame(1, $data['count']);
    }

    public function test_get_price_reports_and_answer(): void
    {
        Http::fake([
            'https://api.torob.com/shop-api/v1/price-reports/answer/*' => Http::response([
                'answered_report_ids' => [4521],
            ], 200),
            'https://api.torob.com/shop-api/v1/price-reports/*' => Http::response([
                'count' => 1,
                'unanswered_count' => 1,
                'results' => [
                    ['report_id' => 4521, 'status' => 'received'],
                ],
            ], 200),
        ]);

        $reports = $this->client->getPriceReports();
        $this->assertSame(1, $reports['unanswered_count']);

        $answerRes = $this->client->answerPriceReports([4521], 'claimed_to_be_corrected', 'Fixed price');
        $this->assertSame([4521], $answerRes['answered_report_ids']);
    }

    public function test_charge_transfer_preview_and_execute(): void
    {
        Http::fake([
            'https://api.torob.com/shop-api/v1/charge-transfer/?destination=other.ir' => Http::response([
                'destination' => ['domain' => 'other.ir'],
                'transferable_cash' => 20000,
            ], 200),
            'https://api.torob.com/shop-api/v1/charge-transfer/' => Http::response([
                'request_id' => 'req-1',
                'amount' => 7000,
                'balance' => 13000,
            ], 201),
        ]);

        $preview = $this->client->getChargeTransferPreview('other.ir');
        $this->assertSame(20000, $preview['transferable_cash']);

        $transfer = $this->client->transferCharge('other.ir', 7000, 'req-1');
        $this->assertSame(13000, $transfer['balance']);
    }
}
