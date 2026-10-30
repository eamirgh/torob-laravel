<?php

namespace Torob\Laravel\Clients;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Torob\Laravel\Exceptions\TorobApiException;

class TorobShopClient
{
    public const DEFAULT_BASE_URL = 'https://api.torob.com/shop-api/v1/';

    public function __construct(
        protected ?string $token = null,
        protected ?string $defaultDomain = null,
        protected string $baseUrl = self::DEFAULT_BASE_URL
    ) {
        $this->token ??= config('torob.shop_token') ?? config('torob.shop_generator_token');
        $this->defaultDomain ??= config('torob.shop_domain');
        $this->baseUrl = rtrim($this->baseUrl, '/') . '/';
    }

    protected function resolveDomain(?string $domain): ?string
    {
        return $domain ?? $this->defaultDomain;
    }

    protected function request(string $method, string $path, array $queryParams = [], array $body = []): array
    {
        $url = $this->baseUrl . ltrim($path, '/');
        if (!empty($queryParams)) {
            $url .= '?' . http_build_query(array_filter($queryParams, fn ($v) => $v !== null && $v !== ''));
        }

        $pending = Http::withToken($this->token)->acceptJson();

        $response = match (strtoupper($method)) {
            'GET' => $pending->get($url),
            'POST' => $pending->asJson()->post($url, $body),
            'DELETE' => $pending->delete($url),
            default => throw new TorobApiException("Unsupported HTTP method: {$method}"),
        };

        if ($response->failed()) {
            $this->handleError($response);
        }

        return $response->json() ?? [];
    }

    protected function handleError(Response $response): void
    {
        $msg = $response->json('error') ?? $response->json('detail') ?? $response->body();
        throw new TorobApiException(
            "Torob Shop API error ({$response->status()}): {$msg}",
            $response->status(),
            $response
        );
    }

    /**
     * GET /shop/
     */
    public function getShop(?string $domain = null): array
    {
        return $this->request('GET', 'shop/', [
            'domain' => $this->resolveDomain($domain),
        ]);
    }

    /**
     * POST /products/
     */
    public function getProducts(
        array $filters = [],
        ?string $domain = null,
        int $pageSize = 100,
        ?string $cursor = null
    ): array {
        $query = [
            'domain' => $this->resolveDomain($domain),
            'page_size' => $pageSize,
            'cursor' => $cursor,
        ];

        return $this->request('POST', 'products/', $query, $filters);
    }

    /**
     * POST /products/activate/
     *
     * @param array<string> $productIds
     */
    public function activateProducts(array $productIds, ?string $domain = null): array
    {
        return $this->request('POST', 'products/activate/', [
            'domain' => $this->resolveDomain($domain),
        ], [
            'product_ids' => array_values($productIds),
        ]);
    }

    /**
     * POST /products/deactivate/
     *
     * @param array<string> $productIds
     */
    public function deactivateProducts(array $productIds, ?string $domain = null): array
    {
        return $this->request('POST', 'products/deactivate/', [
            'domain' => $this->resolveDomain($domain),
        ], [
            'product_ids' => array_values($productIds),
        ]);
    }

    /**
     * GET /clicks/
     */
    public function getClicks(
        string $date,
        array $options = [],
        ?string $domain = null
    ): array {
        $query = array_merge([
            'domain' => $this->resolveDomain($domain),
            'date' => $date,
        ], $options);

        return $this->request('GET', 'clicks/', $query);
    }

    /**
     * GET /price-reports/
     */
    public function getPriceReports(
        array $options = [],
        ?string $domain = null
    ): array {
        $query = array_merge([
            'domain' => $this->resolveDomain($domain),
        ], $options);

        return $this->request('GET', 'price-reports/', $query);
    }

    /**
     * POST /price-reports/answer/
     *
     * @param array<int> $reportIds
     */
    public function answerPriceReports(
        array $reportIds,
        string $status,
        ?string $description = null,
        ?string $domain = null
    ): array {
        $body = [
            'report_ids' => array_values($reportIds),
            'status' => $status,
        ];

        if ($description !== null) {
            $body['description'] = $description;
        }

        return $this->request('POST', 'price-reports/answer/', [
            'domain' => $this->resolveDomain($domain),
        ], $body);
    }

    /**
     * GET /charge-transfer/?destination=...
     */
    public function getChargeTransferPreview(string $destination): array
    {
        return $this->request('GET', 'charge-transfer/', [
            'destination' => $destination,
        ]);
    }

    /**
     * POST /charge-transfer/
     */
    public function transferCharge(string $destination, int $amount, string $requestId): array
    {
        $body = [
            'destination' => $destination,
            'amount' => $amount,
            'request_id' => $requestId,
        ];

        return $this->request('POST', 'charge-transfer/', [], $body);
    }
}
