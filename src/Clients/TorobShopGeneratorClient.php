<?php

namespace Torob\Laravel\Clients;

use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Torob\Laravel\Exceptions\TorobApiException;

class TorobShopGeneratorClient
{
    public const DEFAULT_BASE_URL = 'https://api.torob.com/shop-generator/v1/';

    public function __construct(
        protected ?string $token = null,
        protected string $baseUrl = self::DEFAULT_BASE_URL
    ) {
        $this->token ??= config('torob.shop_generator_token');
        $this->baseUrl = rtrim($this->baseUrl, '/') . '/';
    }

    protected function request(string $method, string $path, array $body = []): Response
    {
        $url = $this->baseUrl . ltrim($path, '/');
        $pending = Http::withToken($this->token)->acceptJson();

        $response = match (strtoupper($method)) {
            'GET' => $pending->get($url),
            'POST' => $pending->asJson()->post($url, $body),
            'DELETE' => $pending->delete($url),
            default => throw new TorobApiException("Unsupported HTTP method: {$method}"),
        };

        if ($response->failed()) {
            $msg = $response->json('error') ?? $response->json('detail') ?? $response->body();
            throw new TorobApiException(
                "Torob Shop Generator API error ({$response->status()}): {$msg}",
                $response->status(),
                $response
            );
        }

        return $response;
    }

    /**
     * Request a connection for a shop domain.
     * POST /connections/
     */
    public function requestConnection(string $shopDomain, string $returnUrl): array
    {
        $response = $this->request('POST', 'connections/', [
            'shop_domain' => $shopDomain,
            'return_url' => $returnUrl,
        ]);

        return $response->json() ?? [];
    }

    /**
     * Get connection details.
     * GET /connections/{connection_id}/
     */
    public function getConnection(string $connectionId): array
    {
        $response = $this->request('GET', "connections/{$connectionId}/");
        return $response->json() ?? [];
    }

    /**
     * Confirm an approved connection with the one-time code.
     * POST /connections/{connection_id}/confirm/
     */
    public function confirmConnection(string $connectionId, string $code): array
    {
        $response = $this->request('POST', "connections/{$connectionId}/confirm/", [
            'code' => $code,
        ]);

        return $response->json() ?? [];
    }

    /**
     * Disconnect an active connection or cancel a pending one.
     * DELETE /connections/{connection_id}/
     */
    public function disconnect(string $connectionId): bool
    {
        $response = $this->request('DELETE', "connections/{$connectionId}/");
        return $response->status() === 204;
    }

    /**
     * Parse and validate the merchant approval redirect callback request.
     *
     * @return array{connection_id: ?string, code: ?string, error: ?string, approved: bool}
     */
    public function parseCallback(Request $request): array
    {
        $connectionId = $request->query('connection_id');
        $code = $request->query('code');
        $error = $request->query('error');

        return [
            'connection_id' => $connectionId,
            'code' => $code,
            'error' => $error,
            'approved' => !empty($code) && empty($error),
        ];
    }
}
