<?php

namespace Torob\Laravel\Tests\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Torob\Laravel\Tests\TestCase;

class TorobClidMigrationTest extends TestCase
{
    public function test_blueprint_macro_adds_torob_clid_column(): void
    {
        Schema::create('orders_test_macro', function (Blueprint $table) {
            $table->id();
            $table->torobClid();
        });

        $this->assertTrue(Schema::hasColumn('orders_test_macro', 'torob_clid'));
    }

    public function test_migration_stub_applies_and_rolls_back(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('total');
            $table->timestamps();
        });

        $this->assertFalse(Schema::hasColumn('orders', 'torob_clid'));

        $migration = require __DIR__ . '/../../database/migrations/add_torob_clid_to_orders_table.php.stub';

        // Run up
        $migration->up();
        $this->assertTrue(Schema::hasColumn('orders', 'torob_clid'));

        // Run down
        $migration->down();
        $this->assertFalse(Schema::hasColumn('orders', 'torob_clid'));
    }

    public function test_migration_uses_custom_configured_orders_table(): void
    {
        config(['torob.tables.orders' => 'custom_shop_orders']);

        Schema::create('custom_shop_orders', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        $migration = require __DIR__ . '/../../database/migrations/add_torob_clid_to_orders_table.php.stub';

        $migration->up();
        $this->assertTrue(Schema::hasColumn('custom_shop_orders', 'torob_clid'));

        $migration->down();
        $this->assertFalse(Schema::hasColumn('custom_shop_orders', 'torob_clid'));
    }
}
