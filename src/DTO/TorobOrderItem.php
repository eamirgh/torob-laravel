<?php

namespace Torob\Laravel\DTO;

use Illuminate\Contracts\Support\Arrayable;
use InvalidArgumentException;
use Torob\Laravel\Contracts\TorobOrderItemContract;

class TorobOrderItem implements Arrayable
{
    public function __construct(
        public string $productUrl,
        public int $productPrice,
        public int $quantity,
    ) {
        if (empty($this->productUrl) || !filter_var($this->productUrl, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('productUrl must be a valid URL');
        }

        if ($this->quantity < 1) {
            throw new InvalidArgumentException('quantity must be at least 1');
        }
    }

    public static function from(mixed $item): self
    {
        if ($item instanceof self) {
            return $item;
        }

        if ($item instanceof TorobOrderItemContract) {
            return new self(
                productUrl: $item->getTorobProductUrl(),
                productPrice: $item->getTorobProductPrice(),
                quantity: $item->getTorobQuantity()
            );
        }

        if (is_array($item)) {
            return self::fromArray($item);
        }

        throw new InvalidArgumentException('Cannot convert value to TorobOrderItem');
    }

    public static function fromArray(array $data): self
    {
        return new self(
            productUrl: (string) ($data['product_url'] ?? ''),
            productPrice: (int) ($data['product_price'] ?? 0),
            quantity: (int) ($data['quantity'] ?? 1),
        );
    }

    public function toArray(): array
    {
        return [
            'product_url' => $this->productUrl,
            'product_price' => $this->productPrice,
            'quantity' => $this->quantity,
        ];
    }
}
