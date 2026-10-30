<?php

namespace Torob\Laravel\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Torob\Laravel\Contracts\TorobProductProviderContract;
use Torob\Laravel\DTO\TorobProduct;

class ProductApiController extends Controller
{
    private const ALLOWED_SORTS = [
        'date_added_desc',
        'date_updated_desc',
        'product_id_desc',
    ];

    public function __invoke(Request $request, TorobProductProviderContract $provider): JsonResponse
    {
        $payload = $request->all();

        if (empty($payload)) {
            return new JsonResponse(['error' => 'Request body cannot be empty'], 400);
        }

        // Option 1: page_urls
        if (isset($payload['page_urls'])) {
            if (!is_array($payload['page_urls']) || empty($payload['page_urls'])) {
                return new JsonResponse(['error' => 'page_urls must be a non-empty array of strings'], 400);
            }

            $products = $provider->getByUrls($payload['page_urls']);
            return new JsonResponse([
                'api_version' => 'torob_api_v3',
                'current_page' => 1,
                'total' => count($products),
                'max_pages' => 1,
                'products' => array_map(fn ($p) => TorobProduct::from($p)->toArray(), $products),
            ]);
        }

        // Option 2: page_uniques
        if (isset($payload['page_uniques'])) {
            if (!is_array($payload['page_uniques']) || empty($payload['page_uniques'])) {
                return new JsonResponse(['error' => 'page_uniques must be a non-empty array of strings'], 400);
            }

            $products = $provider->getByUniques($payload['page_uniques']);
            return new JsonResponse([
                'api_version' => 'torob_api_v3',
                'current_page' => 1,
                'total' => count($products),
                'max_pages' => 1,
                'products' => array_map(fn ($p) => TorobProduct::from($p)->toArray(), $products),
            ]);
        }

        // Option 3 & 4: sorting required
        if (isset($payload['page']) && !isset($payload['sort'])) {
            return new JsonResponse(['error' => 'sort parameter is not provided'], 400);
        }

        $sort = $payload['sort'] ?? null;
        if ($sort === null || !in_array($sort, self::ALLOWED_SORTS, true)) {
            return new JsonResponse(['error' => 'Invalid sort parameter'], 400);
        }

        // Option 3: page-based pagination
        if (isset($payload['page'])) {
            $page = filter_var($payload['page'], FILTER_VALIDATE_INT);
            if ($page === false || $page < 1) {
                return new JsonResponse(['error' => 'page must be an integer >= 1'], 400);
            }

            $result = $provider->getPaginated((int) $page, $sort);

            return new JsonResponse([
                'api_version' => 'torob_api_v3',
                'current_page' => $result->currentPage,
                'total' => $result->total,
                'max_pages' => $result->maxPages,
                'products' => array_map(fn ($p) => TorobProduct::from($p)->toArray(), $result->products),
            ]);
        }

        // Option 4: cursor-based pagination
        if ($sort !== 'product_id_desc') {
            return new JsonResponse(['error' => 'Cursor pagination requires product_id_desc sort'], 400);
        }

        if (isset($payload['page']) || isset($payload['limit']) || isset($payload['size'])) {
            return new JsonResponse(['error' => 'Cursor pagination must not include page, limit, or size'], 400);
        }

        $cursor = isset($payload['cursor']) ? (string) $payload['cursor'] : null;
        $result = $provider->getCursor($cursor, $sort);

        $response = [
            'api_version' => 'torob_api_v3',
            'next_cursor' => $result->nextCursor,
            'total' => $result->total,
            'max_pages' => $result->maxPages,
            'products' => array_map(fn ($p) => TorobProduct::from($p)->toArray(), $result->products),
        ];

        return new JsonResponse($response);
    }
}
