<?php

namespace Torob\Laravel\DTO;

use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use InvalidArgumentException;
use Torob\Laravel\Contracts\TorobOrderContract;

class TorobOrder implements Arrayable
{
    /**
     * @param array<TorobOrderItem>|null $products
     */
    public function __construct(
        public string $purchaseTimestamp,
        public string $lastUpdatedTimestamp,
        public string $torobClid,
        public string $status,
        public ?string $psp = null,
        public ?int $orderValue = null,
        public ?int $shippingAmount = null,
        public ?string $phoneNumber = null,
        public ?array $products = null,
    ) {
        if (!in_array($this->status, ['completed', 'cancelled'], true)) {
            throw new InvalidArgumentException("Status must be either 'completed' or 'cancelled', got '{$this->status}'");
        }

        if (empty($this->torobClid)) {
            throw new InvalidArgumentException('torobClid cannot be empty');
        }
    }

    public static function from(mixed $item): self
    {
        if ($item instanceof self) {
            return $item;
        }

        if ($item instanceof TorobOrderContract) {
            $items = null;
            $rawItems = $item->getTorobItems();
            if ($rawItems !== null && is_array($rawItems)) {
                $items = array_map(fn ($i) => TorobOrderItem::from($i), $rawItems);
            }

            return new self(
                purchaseTimestamp: self::formatTimestamp($item->getTorobPurchaseTimestamp()),
                lastUpdatedTimestamp: self::formatTimestamp($item->getTorobLastUpdatedTimestamp()),
                torobClid: $item->getTorobClid(),
                status: $item->getTorobStatus(),
                psp: $item->getTorobPsp(),
                orderValue: $item->getTorobOrderValue(),
                shippingAmount: $item->getTorobShippingAmount(),
                phoneNumber: $item->getTorobPhoneNumber(),
                products: $items
            );
        }

        if (is_array($item)) {
            return self::fromArray($item);
        }

        throw new InvalidArgumentException('Cannot convert value to TorobOrder');
    }

    public static function fromArray(array $data): self
    {
        $products = null;
        if (isset($data['products']) && is_array($data['products'])) {
            $products = array_map(fn ($item) => TorobOrderItem::from($item), $data['products']);
        }

        return new self(
            purchaseTimestamp: (string) ($data['purchase_timestamp'] ?? ''),
            lastUpdatedTimestamp: (string) ($data['last_updated_timestamp'] ?? ''),
            torobClid: (string) ($data['torob_clid'] ?? ''),
            status: (string) ($data['status'] ?? 'completed'),
            psp: isset($data['psp']) ? (string) $data['psp'] : null,
            orderValue: isset($data['order_value']) ? (int) $data['order_value'] : null,
            shippingAmount: isset($data['shipping_amount']) ? (int) $data['shipping_amount'] : null,
            phoneNumber: isset($data['phone_number']) ? (string) $data['phone_number'] : null,
            products: $products,
        );
    }

    protected static function formatTimestamp(string|DateTimeInterface|null $date): ?string
    {
        if ($date === null) {
            return null;
        }

        if ($date instanceof DateTimeInterface) {
            return $date->format(DateTimeInterface::ATOM);
        }

        return $date;
    }

    public function toArray(): array
    {
        $data = [
            'purchase_timestamp' => $this->purchaseTimestamp,
            'last_updated_timestamp' => $this->lastUpdatedTimestamp,
            'torob_clid' => $this->torobClid,
            'status' => $this->status,
        ];

        if ($this->psp !== null) {
            $data['psp'] = $this->psp;
        }

        if ($this->orderValue !== null) {
            $data['order_value'] = $this->orderValue;
        }

        if ($this->shippingAmount !== null) {
            $data['shipping_amount'] = $this->shippingAmount;
        }

        if ($this->phoneNumber !== null) {
            $data['phone_number'] = $this->phoneNumber;
        }

        if ($this->products !== null) {
            $data['products'] = array_map(
                fn ($p) => $p instanceof TorobOrderItem ? $p->toArray() : $p,
                $this->products
            );
        }

        return $data;
    }
}
