<?php

namespace Torob\Laravel\Tests\Clients;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Torob\Laravel\Clients\TorobShopGeneratorClient;
use Torob\Laravel\Exceptions\TorobApiException;
use Torob\Laravel\Tests\TestCase;

class ShopGeneratorClientTest extends TestCase
{
    private TorobShopGeneratorClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new TorobShopGeneratorClient(token: 'generator-token');
    }

    public function test_request_connection(): void
    {
        Http::fake([
            'https://api.torob.com/shop-generator/v1/connections/' => Http::response([
                'connection_id' => '75a60226-4997-4ae3-bd10-68f7014e0779',
                'shop_domain' => 'example.ir',
                'status' => 'pending',
                'approve_url' => 'https://api.torob.com/shop-panel/shop-connections/75a60226-4997-4ae3-bd10-68f7014e0779/approve/',
            ], 200),
        ]);

        $conn = $this->client->requestConnection('example.ir', 'https://generator.example/callback');

        $this->assertSame('75a60226-4997-4ae3-bd10-68f7014e0779', $conn['connection_id']);
        $this->assertSame('pending', $conn['status']);
        $this->assertNotNull($conn['approve_url']);
    }

    public function test_get_connection(): void
    {
        Http::fake([
            'https://api.torob.com/shop-generator/v1/connections/conn-uuid/' => Http::response([
                'connection_id' => 'conn-uuid',
                'shop_domain' => 'example.ir',
                'status' => 'approved',
                'approve_url' => null,
            ], 200),
        ]);

        $conn = $this->client->getConnection('conn-uuid');

        $this->assertSame('approved', $conn['status']);
    }

    public function test_confirm_connection(): void
    {
        Http::fake([
            'https://api.torob.com/shop-generator/v1/connections/conn-uuid/confirm/' => Http::response([
                'connection_id' => 'conn-uuid',
                'shop_domain' => 'example.ir',
                'status' => 'active',
                'approve_url' => null,
            ], 200),
        ]);

        $conn = $this->client->confirmConnection('conn-uuid', 'one-time-code-123');

        $this->assertSame('active', $conn['status']);
    }

    public function test_disconnect(): void
    {
        Http::fake([
            'https://api.torob.com/shop-generator/v1/connections/conn-uuid/' => Http::response(null, 204),
        ]);

        $result = $this->client->disconnect('conn-uuid');

        $this->assertTrue($result);
    }

    public function test_parse_callback(): void
    {
        $approvedRequest = Request::create('https://generator.example/callback?connection_id=conn-1&code=auth-code');
        $callback = $this->client->parseCallback($approvedRequest);

        $this->assertTrue($callback['approved']);
        $this->assertSame('conn-1', $callback['connection_id']);
        $this->assertSame('auth-code', $callback['code']);

        $deniedRequest = Request::create('https://generator.example/callback?connection_id=conn-1&error=denied');
        $deniedCallback = $this->client->parseCallback($deniedRequest);

        $this->assertFalse($deniedCallback['approved']);
        $this->assertSame('denied', $deniedCallback['error']);
    }
}
