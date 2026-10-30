<?php

namespace Torob\Laravel\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Torob\Laravel\Contracts\TorobOrderProviderContract;
use Torob\Laravel\DTO\TorobOrder;

class OrderApiController extends Controller
{
    public function __invoke(Request $request, TorobOrderProviderContract $provider): JsonResponse
    {
        if (!config('torob.order_tracking_enabled', true) || !$provider->isOrderTrackingEnabled()) {
            return new JsonResponse(['error' => 'Order tracking is disabled for this shop'], 403);
        }

        $purchaseTimestampGt = $request->query('purchase_timestamp_gt');
        if (empty($purchaseTimestampGt) || !is_string($purchaseTimestampGt)) {
            return new JsonResponse(['error' => 'purchase_timestamp_gt parameter is required'], 400);
        }

        $limitRaw = $request->query('limit');
        if ($limitRaw === null || !is_numeric($limitRaw)) {
            return new JsonResponse(['error' => 'limit parameter is required'], 400);
        }

        $limit = (int) $limitRaw;
        if ($limit < 1 || $limit > 1000) {
            return new JsonResponse(['error' => 'limit must be an integer between 1 and 1000'], 400);
        }

        $orders = $provider->getOrders($purchaseTimestampGt, $limit);

        return new JsonResponse([
            'success' => true,
            'data' => array_map(fn ($o) => TorobOrder::from($o)->toArray(), $orders),
        ]);
    }
}
