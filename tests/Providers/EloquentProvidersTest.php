<?php

namespace Torob\Laravel\Tests\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Torob\Laravel\Contracts\TorobOrderContract;
use Torob\Laravel\Contracts\TorobProductContract;
use Torob\Laravel\Contracts\TorobProductProviderContract;
use Torob\Laravel\Contracts\TorobOrderProviderContract;
use Torob\Laravel\Providers\EloquentOrderProvider;
use Torob\Laravel\Providers\EloquentProductProvider;
use Torob\Laravel\Tests\TestCase;
use Torob\Laravel\Traits\HasTorobOrder;
use Torob\Laravel\Traits\HasTorobProduct;

class TestProduct extends Model implements TorobProductContract
{
    use HasTorobProduct;

    protected $table = 'test_products';
    protected $guarded = [];
}

class TestOrder extends Model implements TorobOrderContract
{
    use HasTorobOrder;

    protected $table = 'test_orders';
    protected $guarded = [];
}

class EloquentProvidersTest extends TestCase
{
    protected function defineDatabaseMigrations(): void
    {
        Schema::create('test_products', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('page_url')->nullable();
            $table->integer('price')->default(0);
            $table->boolean('availability')->default(true);
            $table->timestamps();
        });

        Schema::create('test_orders', function (Blueprint $table) {
            $table->id();
            $table->string('torob_clid')->nullable();
            $table->string('status')->default('completed');
            $table->integer('total')->default(0);
            $table->timestamps();
        });
    }

    public function test_eloquent_product_provider_queries_models_directly(): void
    {
        TestProduct::create([
            'id' => 10,
            'title' => 'Product 10',
            'page_url' => 'https://example.com/p/10',
            'price' => 100000,
        ]);
        TestProduct::create([
            'id' => 20,
            'title' => 'Product 20',
            'page_url' => 'https://example.com/p/20',
            'price' => 200000,
        ]);

        $provider = new EloquentProductProvider(TestProduct::class);

        $byUniques = $provider->getByUniques(['10', '99']);
        $this->assertCount(1, $byUniques);
        $this->assertSame('10', $byUniques[0]->getTorobPageUnique());

        $byUrls = $provider->getByUrls(['https://example.com/p/20']);
        $this->assertCount(1, $byUrls);
        $this->assertSame('20', $byUrls[0]->getTorobPageUnique());

        $paginated = $provider->getPaginated(1, 'date_added_desc');
        $this->assertSame(2, $paginated->total);

        $cursor = $provider->getCursor(null, 'product_id_desc');
        $this->assertCount(2, $cursor->products);
    }

    public function test_eloquent_order_provider_queries_models_directly(): void
    {
        TestOrder::create([
            'id' => 1,
            'torob_clid' => 'clid-1',
            'status' => 'completed',
            'total' => 50000,
            'created_at' => '2025-01-01 10:00:00',
        ]);
        TestOrder::create([
            'id' => 2,
            'torob_clid' => null, // non-torob order
            'status' => 'completed',
            'total' => 70000,
            'created_at' => '2025-01-01 11:00:00',
        ]);

        $provider = new EloquentOrderProvider(TestOrder::class);

        $orders = $provider->getOrders('2025-01-01T09:00:00Z', 10);
        $this->assertCount(1, $orders);
        $this->assertSame('clid-1', $orders[0]->getTorobClid());
    }

    public function test_auto_binds_eloquent_providers_from_config(): void
    {
        config(['torob.models.product' => TestProduct::class]);
        config(['torob.models.order' => TestOrder::class]);

        $productProvider = $this->app->make(TorobProductProviderContract::class);
        $this->assertInstanceOf(EloquentProductProvider::class, $productProvider);

        $orderProvider = $this->app->make(TorobOrderProviderContract::class);
        $this->assertInstanceOf(EloquentOrderProvider::class, $orderProvider);
    }
}
