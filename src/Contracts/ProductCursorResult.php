<?php

namespace Torob\Laravel\Contracts;

use Torob\Laravel\DTO\TorobProduct;

class ProductCursorResult
{
    /**
     * @param array<TorobProduct> $products
     */
    public function __construct(
        public array $products,
        public ?string $nextCursor = null,
        public ?int $total = null,
        public ?int $maxPages = null,
    ) {
    }
}
