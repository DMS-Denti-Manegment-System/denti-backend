<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create products table if not exists
        if (! Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('sku')->nullable();
                $table->text('description')->nullable();
                $table->string('unit')->default('adet');
                $table->string('category')->nullable();
                $table->string('brand')->nullable();

                $table->integer('min_stock_level')->default(10);
                $table->integer('critical_stock_level')->default(5);
                $table->integer('yellow_alert_level')->default(10);
                $table->integer('red_alert_level')->default(5);

                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['name']);
            });
        }

        // 2. Add product_id to stocks table if not exists
        if (! Schema::hasColumn('stocks', 'product_id')) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->foreignId('product_id')->nullable()->after('id')->constrained('products')->onDelete('cascade');
            });
        }

        // Data Migration (No company_id)
        $stocks = DB::table('stocks')->whereNull('product_id')->get();
        foreach ($stocks as $stock) {
            $productId = DB::table('products')->where('name', $stock->name)
                ->value('id');

            if (! $productId) {
                $productId = DB::table('products')->insertGetId([
                    'name' => $stock->name,
                    'sku' => $stock->code,
                    'description' => $stock->description,
                    'unit' => $stock->unit,
                    'category' => $stock->category,
                    'brand' => $stock->brand,
                    'min_stock_level' => $stock->min_stock_level,
                    'critical_stock_level' => $stock->critical_stock_level,
                    'yellow_alert_level' => $stock->yellow_alert_level,
                    'red_alert_level' => $stock->red_alert_level,
                    'is_active' => $stock->is_active,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('stocks')->where('id', $stock->id)->update(['product_id' => $productId]);
        }

        // 4. Clean up stocks table
        // Drop indexes one by one (to ignore if already dropped)
        try {
            Schema::table('stocks', function (Blueprint $table) {
                $table->dropIndex(['name', 'status']);
            });
        } catch (\Exception $e) {
        }
        try {
            Schema::table('stocks', function (Blueprint $table) {
                $table->dropIndex(['current_stock', 'min_stock_level']);
            });
        } catch (\Exception $e) {
        }
        try {
            Schema::table('stocks', function (Blueprint $table) {
                $table->dropUnique(['code']);
            });
        } catch (\Exception $e) {
        }

        // Finally drop columns
        Schema::table('stocks', function (Blueprint $table) {
            $columnsToDrop = [];
            foreach (['name', 'code', 'description', 'unit', 'category', 'brand', 'min_stock_level', 'critical_stock_level', 'yellow_alert_level', 'red_alert_level'] as $col) {
                if (Schema::hasColumn('stocks', $col)) {
                    $columnsToDrop[] = $col;
                }
            }

            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // To rollback, we would need to recreate columns and move data back
        // For brevity in this task, we just drop the table and revert changes
        Schema::table('stocks', function (Blueprint $table) {
            $table->string('name')->nullable();
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->string('unit')->nullable();
            $table->string('category')->nullable();
            $table->string('brand')->nullable();
            $table->integer('min_stock_level')->default(10);
            $table->integer('critical_stock_level')->default(5);
            $table->integer('yellow_alert_level')->default(10);
            $table->integer('red_alert_level')->default(5);
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
        });

        Schema::dropIfExists('products');
    }
};
