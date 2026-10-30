<?php

namespace Torob\Laravel\Tests\Clients;

use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Torob\Laravel\Clients\TorobWebhookClient;
use Torob\Laravel\Exceptions\TorobApiException;
use Torob\Laravel\Tests\TestCase;

class WebhookClientTest extends TestCase
{
    public function test_sends_product_update_successfully(): void
    {
        Http::fake([
            'https://api.torob.com/update/webhook/v1/' => Http::response(['message' => 'success'], 200),
        ]);

        $client = new TorobWebhookClient(token: 'test-token');

        $result = $client->notifyProduct('https://domain.com/product/34/', '12412_1');

        $this->assertSame(['message' => 'success'], $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.torob.com/update/webhook/v1/'
                && $request->hasHeader('Authorization', 'Bearer test-token')
                && $request['items'] === [
                    [
                        'page_url' => 'https://domain.com/product/34/',
                        'page_unique' => '12412_1',
                    ],
                ];
        });
    }

    public function test_chunks_items_over_100_into_multiple_requests(): void
    {
        Http::fake([
            'https://api.torob.com/update/webhook/v1/' => Http::response(['message' => 'success'], 200),
        ]);

        $client = new TorobWebhookClient(token: 'test-token');

        $items = [];
        for ($i = 1; $i <= 150; $i++) {
            $items[] = [
                'page_url' => "https://domain.com/product/{$i}/",
                'page_unique' => "uid_{$i}",
            ];
        }

        $results = $client->notify($items);

        $this->assertCount(2, $results);
        Http::assertSentCount(2);
    }

    public function test_rejects_mixed_domains(): void
    {
        $client = new TorobWebhookClient(token: 'test-token');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('All page_urls must belong to the same host');

        $client->notify([
            ['page_url' => 'https://domain.com/product/1/', 'page_unique' => '1'],
            ['page_url' => 'https://other-domain.com/product/2/', 'page_unique' => '2'],
        ]);
    }

    public function test_throws_torob_api_exception_on_api_error(): void
    {
        Http::fake([
            'https://api.torob.com/update/webhook/v1/' => Http::response(['error' => 'Invalid token'], 401),
        ]);

        $client = new TorobWebhookClient(token: 'bad-token');

        $this->expectException(TorobApiException::class);
        $client->notifyProduct('https://domain.com/product/1/', '1');
    }
}
