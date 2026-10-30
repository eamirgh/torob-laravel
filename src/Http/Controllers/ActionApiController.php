<?php

namespace Torob\Laravel\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Torob\Laravel\Contracts\TorobActionProviderContract;
use Torob\Laravel\DTO\TorobAction;

class ActionApiController extends Controller
{
    public function __invoke(Request $request, TorobActionProviderContract $provider): JsonResponse
    {
        if (!config('torob.action_tracking_enabled', true) || !$provider->isActionTrackingEnabled()) {
            return new JsonResponse(['error' => 'Action tracking is disabled for this shop'], 403);
        }

        $timestampGt = $request->query('timestamp_gt');
        if (empty($timestampGt) || !is_string($timestampGt)) {
            return new JsonResponse(['error' => 'timestamp_gt parameter is required'], 400);
        }

        $limitRaw = $request->query('limit');
        if ($limitRaw === null || !is_numeric($limitRaw)) {
            return new JsonResponse(['error' => 'limit parameter is required'], 400);
        }

        $limit = (int) $limitRaw;
        if ($limit < 1 || $limit > 1000) {
            return new JsonResponse(['error' => 'limit must be an integer between 1 and 1000'], 400);
        }

        $actions = $provider->getActions($timestampGt, $limit);

        return new JsonResponse([
            'success' => true,
            'data' => array_map(fn ($a) => TorobAction::from($a)->toArray(), $actions),
        ]);
    }
}
