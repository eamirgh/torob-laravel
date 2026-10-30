<?php

namespace Torob\Laravel\DTO;

use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use InvalidArgumentException;
use Torob\Laravel\Contracts\TorobProductContract;

class TorobProduct implements Arrayable
{
    /**
     * @param array<string> $imageLinks
     * @param array<string, mixed> $spec
     */
    public function __construct(
        public string $pageUnique,
        public string $pageUrl,
        public string $title,
        public int $currentPrice,
        public bool $availability,
        public array $imageLinks,
        public string $dateAdded,
        public array $spec = [],
        public ?string $subtitle = null,
        public ?string $productGroupId = null,
        public ?int $oldPrice = null,
        public ?string $categoryName = null,
        public ?string $shortDesc = null,
        public ?string $guarantee = null,
        public ?string $dateUpdated = null,
        public ?string $sellerName = null,
        public ?string $sellerCity = null,
    ) {
        if (empty($this->pageUnique)) {
            throw new InvalidArgumentException('pageUnique cannot be empty');
        }

        if (empty($this->pageUrl) || !filter_var($this->pageUrl, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('pageUrl must be a valid absolute URL');
        }

        if (empty($this->title)) {
            throw new InvalidArgumentException('title cannot be empty');
        }

        if (empty($this->imageLinks)) {
            throw new InvalidArgumentException('imageLinks must contain at least one image');
        }
    }

    public static function from(mixed $item): self
    {
        if ($item instanceof self) {
            return $item;
        }

        if ($item instanceof TorobProductContract) {
            return new self(
                pageUnique: $item->getTorobPageUnique(),
                pageUrl: $item->getTorobPageUrl(),
                title: $item->getTorobTitle(),
                currentPrice: $item->getTorobCurrentPrice(),
                availability: $item->getTorobAvailability(),
                imageLinks: $item->getTorobImageLinks(),
                dateAdded: self::formatTimestamp($item->getTorobDateAdded()),
                spec: $item->getTorobSpec(),
                subtitle: $item->getTorobSubtitle(),
                productGroupId: $item->getTorobProductGroupId(),
                oldPrice: $item->getTorobOldPrice(),
                categoryName: $item->getTorobCategoryName(),
                shortDesc: $item->getTorobShortDesc(),
                guarantee: $item->getTorobGuarantee(),
                dateUpdated: self::formatTimestamp($item->getTorobDateUpdated()),
                sellerName: $item->getTorobSellerName(),
                sellerCity: $item->getTorobSellerCity(),
            );
        }

        if (is_array($item)) {
            return self::fromArray($item);
        }

        throw new InvalidArgumentException('Cannot convert value to TorobProduct');
    }

    public static function fromArray(array $data): self
    {
        return new self(
            pageUnique: (string) ($data['page_unique'] ?? ''),
            pageUrl: (string) ($data['page_url'] ?? ''),
            title: (string) ($data['title'] ?? ''),
            currentPrice: (int) ($data['current_price'] ?? 0),
            availability: (bool) ($data['availability'] ?? false),
            imageLinks: (array) ($data['image_links'] ?? []),
            dateAdded: (string) ($data['date_added'] ?? ''),
            spec: (array) ($data['spec'] ?? []),
            subtitle: isset($data['subtitle']) ? (string) $data['subtitle'] : null,
            productGroupId: isset($data['product_group_id']) ? (string) $data['product_group_id'] : null,
            oldPrice: isset($data['old_price']) ? (int) $data['old_price'] : null,
            categoryName: isset($data['category_name']) ? (string) $data['category_name'] : null,
            shortDesc: isset($data['short_desc']) ? (string) $data['short_desc'] : null,
            guarantee: isset($data['guarantee']) ? (string) $data['guarantee'] : null,
            dateUpdated: isset($data['date_updated']) ? (string) $data['date_updated'] : null,
            sellerName: isset($data['seller_name']) ? (string) $data['seller_name'] : null,
            sellerCity: isset($data['seller_city']) ? (string) $data['seller_city'] : null,
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
            'page_unique' => $this->pageUnique,
            'page_url' => $this->pageUrl,
            'title' => $this->title,
            'current_price' => $this->currentPrice,
            'availability' => $this->availability,
            'image_links' => array_values($this->imageLinks),
            'spec' => $this->spec,
            'date_added' => $this->dateAdded,
        ];

        if ($this->subtitle !== null) {
            $data['subtitle'] = $this->subtitle;
        }

        if ($this->productGroupId !== null) {
            $data['product_group_id'] = $this->productGroupId;
        }

        if ($this->oldPrice !== null) {
            $data['old_price'] = $this->oldPrice;
        }

        if ($this->categoryName !== null) {
            $data['category_name'] = $this->categoryName;
        }

        if ($this->shortDesc !== null) {
            $data['short_desc'] = $this->shortDesc;
        }

        if ($this->guarantee !== null) {
            $data['guarantee'] = $this->guarantee;
        }

        if ($this->dateUpdated !== null) {
            $data['date_updated'] = $this->dateUpdated;
        }

        if ($this->sellerName !== null) {
            $data['seller_name'] = $this->sellerName;
        }

        if ($this->sellerCity !== null) {
            $data['seller_city'] = $this->sellerCity;
        }

        return $data;
    }
}
