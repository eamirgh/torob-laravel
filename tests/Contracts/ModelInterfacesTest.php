<?php

namespace Torob\Laravel\Tests\Contracts;

use DateTimeImmutable;
use Torob\Laravel\Contracts\TorobActionContract;
use Torob\Laravel\Contracts\TorobOrderContract;
use Torob\Laravel\Contracts\TorobOrderItemContract;
use Torob\Laravel\Contracts\TorobProductContract;
use Torob\Laravel\DTO\TorobAction;
use Torob\Laravel\DTO\TorobOrder;
use Torob\Laravel\DTO\TorobOrderItem;
use Torob\Laravel\DTO\TorobProduct;
use Torob\Laravel\Tests\TestCase;

class ModelInterfacesTest extends TestCase
{
    public function test_converts_product_model_interface_to_dto(): void
    {
        $productModel = new class implements TorobProductContract {
            public function getTorobPageUnique(): string
            {
                return 'prod-101';
            }

            public function getTorobPageUrl(): string
            {
                return 'https://example.com/product/101';
            }

            public function getTorobTitle(): string
            {
                return 'Sample Product';
            }

            public function getTorobCurrentPrice(): int
            {
                return 450000;
            }

            public function getTorobAvailability(): bool
            {
                return true;
            }

            public function getTorobImageLinks(): array
            {
                return ['https://example.com/images/101.jpg'];
            }

            public function getTorobDateAdded(): string|\DateTimeInterface
            {
                return new DateTimeImmutable('2025-05-10T12:00:00Z');
            }

            public function getTorobSpec(): array
            {
                return ['color' => 'Blue'];
            }

            public function getTorobSubtitle(): ?string
            {
                return 'Sub title';
            }

            public function getTorobProductGroupId(): ?string
            {
                return 'group-1';
            }

            public function getTorobOldPrice(): ?int
            {
                return 500000;
            }

            public function getTorobCategoryName(): ?string
            {
                return 'Electronics';
            }

            public function getTorobShortDesc(): ?string
            {
                return 'Brief description';
            }

            public function getTorobGuarantee(): ?string
            {
                return '18 months';
            }

            public function getTorobDateUpdated(): string|\DateTimeInterface|null
            {
                return '2025-05-11T12:00:00Z';
            }

            public function getTorobSellerName(): ?string
            {
                return 'Main Seller';
            }

            public function getTorobSellerCity(): ?string
            {
                return 'Tehran';
            }
        };

        $dto = TorobProduct::from($productModel);

        $this->assertInstanceOf(TorobProduct::class, $dto);
        $this->assertSame('prod-101', $dto->pageUnique);
        $this->assertSame('https://example.com/product/101', $dto->pageUrl);
        $this->assertSame('Sample Product', $dto->title);
        $this->assertSame(450000, $dto->currentPrice);
        $this->assertTrue($dto->availability);
        $this->assertSame(['https://example.com/images/101.jpg'], $dto->imageLinks);
        $this->assertSame('2025-05-10T12:00:00+00:00', $dto->dateAdded);
        $this->assertSame(['color' => 'Blue'], $dto->spec);
        $this->assertSame('Sub title', $dto->subtitle);
        $this->assertSame('500000', (string) $dto->oldPrice);
    }

    public function test_converts_order_and_order_item_interfaces_to_dto(): void
    {
        $itemModel = new class implements TorobOrderItemContract {
            public function getTorobProductUrl(): string
            {
                return 'https://example.com/product/101';
            }

            public function getTorobProductPrice(): int
            {
                return 200000;
            }

            public function getTorobQuantity(): int
            {
                return 2;
            }
        };

        $orderModel = new class ($itemModel) implements TorobOrderContract {
            public function __construct(private TorobOrderItemContract $item)
            {
            }

            public function getTorobPurchaseTimestamp(): string|\DateTimeInterface
            {
                return new DateTimeImmutable('2025-09-21T10:00:00Z');
            }

            public function getTorobLastUpdatedTimestamp(): string|\DateTimeInterface
            {
                return new DateTimeImmutable('2025-09-21T10:05:00Z');
            }

            public function getTorobClid(): string
            {
                return 'clid-xyz';
            }

            public function getTorobStatus(): string
            {
                return 'completed';
            }

            public function getTorobPsp(): ?string
            {
                return 'torobpay';
            }

            public function getTorobOrderValue(): ?int
            {
                return 400000;
            }

            public function getTorobShippingAmount(): ?int
            {
                return 50000;
            }

            public function getTorobPhoneNumber(): ?string
            {
                return '09123456789';
            }

            public function getTorobItems(): array
            {
                return [$this->item];
            }
        };

        $orderDto = TorobOrder::from($orderModel);

        $this->assertInstanceOf(TorobOrder::class, $orderDto);
        $this->assertSame('2025-09-21T10:00:00+00:00', $orderDto->purchaseTimestamp);
        $this->assertSame('2025-09-21T10:05:00+00:00', $orderDto->lastUpdatedTimestamp);
        $this->assertSame('clid-xyz', $orderDto->torobClid);
        $this->assertSame('completed', $orderDto->status);
        $this->assertSame('torobpay', $orderDto->psp);
        $this->assertSame(400000, $orderDto->orderValue);
        $this->assertCount(1, $orderDto->products);
        $this->assertSame(2, $orderDto->products[0]->quantity);
    }

    public function test_converts_action_interface_to_dto(): void
    {
        $actionModel = new class implements TorobActionContract {
            public function getTorobTimestamp(): string|\DateTimeInterface
            {
                return new DateTimeImmutable('2025-09-21T10:00:00Z');
            }

            public function getTorobLastUpdatedTimestamp(): string|\DateTimeInterface
            {
                return new DateTimeImmutable('2025-09-21T10:00:00Z');
            }

            public function getTorobClid(): string
            {
                return 'clid-action';
            }

            public function getTorobStatus(): string
            {
                return 'completed';
            }

            public function getTorobActionType(): string
            {
                return 'form_lead';
            }
        };

        $actionDto = TorobAction::from($actionModel);

        $this->assertInstanceOf(TorobAction::class, $actionDto);
        $this->assertSame('2025-09-21T10:00:00+00:00', $actionDto->timestamp);
        $this->assertSame('clid-action', $actionDto->torobClid);
        $this->assertSame('form_lead', $actionDto->actionType);
    }
}
