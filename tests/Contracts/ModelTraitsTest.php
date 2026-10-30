<?php

namespace Torob\Laravel\Tests\Contracts;

use Torob\Laravel\Contracts\TorobActionContract;
use Torob\Laravel\Contracts\TorobOrderContract;
use Torob\Laravel\Contracts\TorobOrderItemContract;
use Torob\Laravel\Contracts\TorobProductContract;
use Torob\Laravel\DTO\TorobAction;
use Torob\Laravel\DTO\TorobOrder;
use Torob\Laravel\DTO\TorobProduct;
use Torob\Laravel\Tests\TestCase;
use Torob\Laravel\Traits\HasTorobAction;
use Torob\Laravel\Traits\HasTorobOrder;
use Torob\Laravel\Traits\HasTorobOrderItem;
use Torob\Laravel\Traits\HasTorobProduct;

class ModelTraitsTest extends TestCase
{
    public function test_has_torob_product_trait_defaults(): void
    {
        $dummy = new class implements TorobProductContract {
            use HasTorobProduct;

            public $id = 42;
            public $title = 'Dummy Product';
            public $price = 99000;
            public $stock = 5;
            public $image_url = 'https://example.com/img.jpg';
            public $created_at = '2025-01-01T00:00:00Z';

            public function getKey()
            {
                return $this->id;
            }
        };

        $dto = TorobProduct::from($dummy);

        $this->assertSame('42', $dto->pageUnique);
        $this->assertSame(url('/products/42'), $dto->pageUrl);
        $this->assertSame('Dummy Product', $dto->title);
        $this->assertSame(99000, $dto->currentPrice);
        $this->assertTrue($dto->availability);
        $this->assertSame(['https://example.com/img.jpg'], $dto->imageLinks);
    }

    public function test_has_torob_order_and_item_traits_defaults(): void
    {
        $item = new class implements TorobOrderItemContract {
            use HasTorobOrderItem;

            public $product_url = 'https://example.com/product/1';
            public $price = 50000;
            public $qty = 3;
        };

        $order = new class ($item) implements TorobOrderContract {
            use HasTorobOrder;

            public $created_at = '2025-01-01T10:00:00Z';
            public $torob_clid = 'clid-trait';
            public $status = 'completed';
            public $total = 150000;
            public $items;

            public function __construct($item)
            {
                $this->items = [$item];
            }
        };

        $dto = TorobOrder::from($order);

        $this->assertSame('clid-trait', $dto->torobClid);
        $this->assertSame('completed', $dto->status);
        $this->assertSame(150000, $dto->orderValue);
        $this->assertCount(1, $dto->products);
        $this->assertSame(3, $dto->products[0]->quantity);
    }

    public function test_has_torob_action_trait_defaults(): void
    {
        $action = new class implements TorobActionContract {
            use HasTorobAction;

            public $created_at = '2025-01-01T10:00:00Z';
            public $torob_clid = 'clid-action-trait';
            public $status = 'completed';
            public $type = 'newsletter_subscribe';
        };

        $dto = TorobAction::from($action);

        $this->assertSame('clid-action-trait', $dto->torobClid);
        $this->assertSame('newsletter_subscribe', $dto->actionType);
    }
}
