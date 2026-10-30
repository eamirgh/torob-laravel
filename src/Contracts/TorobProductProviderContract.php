<?php

namespace Torob\Laravel\Contracts;

use Torob\Laravel\DTO\TorobProduct;

interface TorobProductProviderContract
{
    /**
     * @param array<string> $urls
     * @return array<TorobProduct>
     */
    public function getByUrls(array $urls): array;

    /**
     * @param array<string> $uniques
     * @return array<TorobProduct>
     */
    public function getByUniques(array $uniques): array;

    /**
     * Fetch products by page and sort.
     */
    public function getPaginated(int $page, string $sort): ProductPageResult;

    /**
     * Fetch products by cursor and sort.
     */
    public function getCursor(?string $cursor, string $sort): ProductCursorResult;
}
