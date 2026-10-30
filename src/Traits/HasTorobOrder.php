<?php

namespace Torob\Laravel\Traits;

use DateTimeInterface;

/**
 * Default implementations of TorobOrderContract for Eloquent models.
 */
trait HasTorobOrder
{
    public function getTorobPurchaseTimestamp(): string|DateTimeInterface
    {
        return $this->purchase_timestamp ?? $this->created_at ?? date(DATE_ATOM);
    }

    public function getTorobLastUpdatedTimestamp(): string|DateTimeInterface
    {
        return $this->last_updated_timestamp ?? $this->updated_at ?? $this->getTorobPurchaseTimestamp();
    }

    public function getTorobClid(): string
    {
        return (string) ($this->torob_clid ?? '');
    }

    public function getTorobStatus(): string
    {
        $status = strtolower((string) ($this->status ?? 'completed'));
        return in_array($status, ['cancelled', 'canceled'], true) ? 'cancelled' : 'completed';
    }

    public function getTorobPsp(): ?string
    {
        return $this->psp ?? $this->gateway ?? null;
    }

    public function getTorobOrderValue(): ?int
    {
        $val = $this->order_value ?? $this->total ?? $this->subtotal ?? null;
        return $val !== null ? (int) $val : null;
    }

    public function getTorobShippingAmount(): ?int
    {
        $val = $this->shipping_amount ?? $this->shipping_cost ?? $this->delivery_fee ?? null;
        return $val !== null ? (int) $val : null;
    }

    public function getTorobPhoneNumber(): ?string
    {
        return $this->phone_number ?? $this->mobile ?? (isset($this->user) ? $this->user?->mobile : null);
    }

    public function getTorobItems(): ?array
    {
        $items = $this->products ?? $this->items ?? $this->orderItems ?? null;
        if ($items === null) {
            return null;
        }

        if (is_iterable($items)) {
            $result = [];
            foreach ($items as $item) {
                $result[] = $item;
            }
            return $result;
        }

        return is_array($items) ? $items : null;
    }
}
