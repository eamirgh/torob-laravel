<?php

namespace Torob\Laravel\Contracts;

use Torob\Laravel\DTO\TorobProduct;

class ProductPageResult
{
    /**
     * @param array<TorobProduct> $products
     */
    public function __construct(
        public array $products,
        public int $total,
        public int $currentPage,
        public int $maxPages,
    ) {
    }
}
