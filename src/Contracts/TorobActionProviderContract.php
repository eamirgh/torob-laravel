<?php

namespace Torob\Laravel\Contracts;

use Torob\Laravel\DTO\TorobAction;

interface TorobActionProviderContract
{
    /**
     * Whether action tracking integration is enabled for the shop.
     */
    public function isActionTrackingEnabled(): bool;

    /**
     * Return actions completed/updated with timestamp > $timestampGt,
     * sorted in ascending order by timestamp, limited to $limit.
     *
     * @return array<TorobAction>
     */
    public function getActions(string $timestampGt, int $limit): array;
}
