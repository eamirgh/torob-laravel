<?php

namespace Torob\Laravel\Contracts;

use DateTimeInterface;

interface TorobProductContract
{
    /**
     * Unique identifier for the product (max 200 chars, e.g. '12412_1').
     */
    public function getTorobPageUnique(): string;

    /**
     * Absolute URL to the product page.
     */
    public function getTorobPageUrl(): string;

    /**
     * Product title (max 500 chars).
     */
    public function getTorobTitle(): string;

    /**
     * Selling price in Toman (integer, 0 for unavailable products).
     */
    public function getTorobCurrentPrice(): int;

    /**
     * Product stock availability status.
     */
    public function getTorobAvailability(): bool;

    /**
     * Array of image URLs (first item is the main image).
     *
     * @return array<string>
     */
    public function getTorobImageLinks(): array;

    /**
     * Date added in ISO 8601 or DateTimeInterface.
     */
    public function getTorobDateAdded(): string|DateTimeInterface;

    /**
     * Key-value specifications map (empty array if none).
     *
     * @return array<string, mixed>
     */
    public function getTorobSpec(): array;

    public function getTorobSubtitle(): ?string;

    public function getTorobProductGroupId(): ?string;

    public function getTorobOldPrice(): ?int;

    public function getTorobCategoryName(): ?string;

    public function getTorobShortDesc(): ?string;

    public function getTorobGuarantee(): ?string;

    public function getTorobDateUpdated(): string|DateTimeInterface|null;

    public function getTorobSellerName(): ?string;

    public function getTorobSellerCity(): ?string;
}
