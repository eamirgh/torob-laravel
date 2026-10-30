<?php

namespace Torob\Laravel\Contracts;

use DateTimeInterface;

interface TorobOrderContract
{
    /**
     * Initial purchase timestamp (UTC ISO 8601 or DateTimeInterface).
     */
    public function getTorobPurchaseTimestamp(): string|DateTimeInterface;

    /**
     * Last modified timestamp (UTC ISO 8601 or DateTimeInterface).
     */
    public function getTorobLastUpdatedTimestamp(): string|DateTimeInterface;

    /**
     * Tracking identifier (torob_clid).
     */
    public function getTorobClid(): string;

    /**
     * Current status ('completed' or 'cancelled').
     */
    public function getTorobStatus(): string;

    /**
     * Payment provider. Use 'torobpay' for TorobPay orders.
     */
    public function getTorobPsp(): ?string;

    /**
     * Total items value in Toman (excluding shipping/taxes).
     */
    public function getTorobOrderValue(): ?int;

    /**
     * Shipping amount in Toman.
     */
    public function getTorobShippingAmount(): ?int;

    /**
     * Customer phone number.
     */
    public function getTorobPhoneNumber(): ?string;

    /**
     * Array of order items.
     *
     * @return array<TorobOrderItemContract|array<string, mixed>>|null
     */
    public function getTorobItems(): ?array;
}
