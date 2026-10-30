<?php

namespace Torob\Laravel\Traits;

use DateTimeInterface;

/**
 * Default implementations of TorobProductContract for Eloquent models.
 */
trait HasTorobProduct
{
    public function getTorobPageUnique(): string
    {
        return (string) ($this->page_unique ?? $this->getKey());
    }

    public function getTorobPageUrl(): string
    {
        return (string) ($this->page_url ?? $this->url ?? url("/products/{$this->getKey()}"));
    }

    public function getTorobTitle(): string
    {
        return (string) ($this->title ?? $this->name ?? '');
    }

    public function getTorobCurrentPrice(): int
    {
        return (int) ($this->current_price ?? $this->price ?? 0);
    }

    public function getTorobAvailability(): bool
    {
        if (isset($this->availability)) {
            return (bool) $this->availability;
        }

        if (isset($this->is_available)) {
            return (bool) $this->is_available;
        }

        if (isset($this->stock)) {
            return (int) $this->stock > 0;
        }

        return true;
    }

    public function getTorobImageLinks(): array
    {
        if (isset($this->image_links) && is_array($this->image_links)) {
            return $this->image_links;
        }

        $singleImage = $this->image ?? $this->image_url ?? $this->main_image ?? null;
        return $singleImage ? [(string) $singleImage] : [];
    }

    public function getTorobDateAdded(): string|DateTimeInterface
    {
        return $this->created_at ?? $this->date_added ?? date(DATE_ATOM);
    }

    public function getTorobSpec(): array
    {
        return (array) ($this->spec ?? $this->specs ?? []);
    }

    public function getTorobSubtitle(): ?string
    {
        return isset($this->subtitle) ? (string) $this->subtitle : null;
    }

    public function getTorobProductGroupId(): ?string
    {
        $id = $this->product_group_id ?? $this->parent_id ?? null;
        return $id !== null ? (string) $id : null;
    }

    public function getTorobOldPrice(): ?int
    {
        $price = $this->old_price ?? $this->original_price ?? null;
        return $price !== null ? (int) $price : null;
    }

    public function getTorobCategoryName(): ?string
    {
        return $this->category_name ?? $this->category?->name ?? null;
    }

    public function getTorobShortDesc(): ?string
    {
        return $this->short_desc ?? $this->summary ?? null;
    }

    public function getTorobGuarantee(): ?string
    {
        return $this->guarantee ?? $this->warranty ?? null;
    }

    public function getTorobDateUpdated(): string|DateTimeInterface|null
    {
        return $this->updated_at ?? $this->date_updated ?? null;
    }

    public function getTorobSellerName(): ?string
    {
        return $this->seller_name ?? null;
    }

    public function getTorobSellerCity(): ?string
    {
        return $this->seller_city ?? null;
    }
}
