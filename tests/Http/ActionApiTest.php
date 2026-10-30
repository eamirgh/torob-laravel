<?php

namespace Torob\Laravel\Tests\Http;

use Torob\Laravel\Contracts\TorobActionProviderContract;
use Torob\Laravel\DTO\TorobAction;
use Torob\Laravel\Tests\TestCase;

class ActionApiTest extends TestCase
{
    private TorobActionProviderContract $provider;
    private bool $enabled = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new class ($this->enabled) implements TorobActionProviderContract {
            public function __construct(private bool &$enabled)
            {
            }

            public function isActionTrackingEnabled(): bool
            {
                return $this->enabled;
            }

            public function getActions(string $timestampGt, int $limit): array
            {
                return [
                    new TorobAction(
                        timestamp: '2025-09-21T10:20:30.456789Z',
                        lastUpdatedTimestamp: '2025-09-21T10:20:30.456789Z',
                        torobClid: 'a1b2c3d4-e5f6-7890-g1h2-i3j4k5l6m7n8',
                        status: 'completed',
                        actionType: 'example_action'
                    ),
                ];
            }
        };

        $this->app->instance(TorobActionProviderContract::class, $this->provider);
    }

    public function test_rejects_when_action_tracking_disabled_with_403(): void
    {
        $this->enabled = false;

        $response = $this->withoutMiddleware()
            ->getJson('/torob/v1/actions?timestamp_gt=2025-09-21T10:00:00.000000Z&limit=100');

        $response->assertStatus(403);
    }

    public function test_rejects_missing_timestamp_gt_with_400(): void
    {
        $response = $this->withoutMiddleware()
            ->getJson('/torob/v1/actions?limit=100');

        $response->assertStatus(400)
            ->assertJson(['error' => 'timestamp_gt parameter is required']);
    }

    public function test_rejects_invalid_limit_with_400(): void
    {
        $response = $this->withoutMiddleware()
            ->getJson('/torob/v1/actions?timestamp_gt=2025-09-21T10:00:00.000000Z&limit=0');

        $response->assertStatus(400)
            ->assertJson(['error' => 'limit must be an integer between 1 and 1000']);
    }

    public function test_fetches_actions_successfully(): void
    {
        $response = $this->withoutMiddleware()
            ->getJson('/torob/v1/actions?timestamp_gt=2025-09-21T10:00:00.000000Z&limit=100');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    [
                        'timestamp' => '2025-09-21T10:20:30.456789Z',
                        'last_updated_timestamp' => '2025-09-21T10:20:30.456789Z',
                        'torob_clid' => 'a1b2c3d4-e5f6-7890-g1h2-i3j4k5l6m7n8',
                        'status' => 'completed',
                        'action_type' => 'example_action',
                    ],
                ],
            ]);
    }
}
