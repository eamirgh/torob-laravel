<?php

namespace Torob\Laravel\Contracts;

use Torob\Laravel\DTO\TorobOrder;

interface TorobOrderProviderContract
{
    /**
     * Whether order tracking integration is enabled for the shop.
     */
    public function isOrderTrackingEnabled(): bool;

    /**
     * Return orders placed/updated with purchase_timestamp > $purchaseTimestampGt,
     * sorted in ascending order by purchase_timestamp, limited to $limit.
     *
     * @return array<TorobOrder>
     */
    public function getOrders(string $purchaseTimestampGt, int $limit): array;
}
