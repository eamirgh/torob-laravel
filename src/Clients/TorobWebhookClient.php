<?php

namespace Torob\Laravel\Clients;

use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Torob\Laravel\Exceptions\TorobApiException;

class TorobWebhookClient
{
    public const DEFAULT_BASE_URL = 'https://api.torob.com/update/webhook/v1/';
    public const MAX_ITEMS_PER_REQUEST = 100;

    public function __construct(
        protected ?string $token = null,
        protected string $endpointUrl = self::DEFAULT_BASE_URL
    ) {
        $this->token ??= config('torob.webhook_token');
    }

    /**
     * Notify Torob of a single product addition or update.
     *
     * @return array<string, mixed>
     */
    public function notifyProduct(string $pageUrl, string $pageUnique): array
    {
        $results = $this->notify([
            [
                'page_url' => $pageUrl,
                'page_unique' => $pageUnique,
            ],
        ]);

        return $results[0] ?? ['message' => 'success'];
    }

    /**
     * Notify Torob of multiple product additions or updates.
     * Batches into chunks of up to 100 items automatically.
     *
     * @param array<array{page_url: string, page_unique: string}> $items
     * @return array<array<string, mixed>>
     */
    public function notify(array $items): array
    {
        if (empty($items)) {
            return [];
        }

        // Validate items and ensure same domain
        $firstHost = null;
        foreach ($items as $item) {
            $url = $item['page_url'] ?? '';
            if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
                throw new InvalidArgumentException("Invalid or missing page_url: {$url}");
            }

            if (empty($item['page_unique'])) {
                throw new InvalidArgumentException("Missing page_unique for URL: {$url}");
            }

            $host = parse_url($url, PHP_URL_HOST);
            if ($firstHost === null) {
                $firstHost = $host;
            } elseif ($firstHost !== $host) {
                throw new InvalidArgumentException("All page_urls must belong to the same host: '{$firstHost}' vs '{$host}'");
            }
        }

        $chunks = array_chunk($items, self::MAX_ITEMS_PER_REQUEST);
        $responses = [];

        foreach ($chunks as $chunk) {
            $response = Http::withToken($this->token)
                ->acceptJson()
                ->asJson()
                ->post($this->endpointUrl, [
                    'items' => array_values($chunk),
                ]);

            if ($response->failed()) {
                $errorMessage = $response->json('error') ?? $response->json('detail') ?? $response->body();
                throw new TorobApiException(
                    "Torob Webhook error ({$response->status()}): {$errorMessage}",
                    $response->status(),
                    $response
                );
            }

            $responses[] = $response->json();
        }

        return $responses;
    }
}
