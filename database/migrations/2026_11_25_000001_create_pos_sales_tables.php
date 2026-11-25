<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_sales', function (Blueprint $table): void {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->decimal('total', 12, 2);
            $table->string('payment_method', 24)->default('EFECTIVO');
            $table->decimal('amount_received', 12, 2);
            $table->decimal('change_amount', 12, 2)->default(0);
            $table->string('origin', 20)->default('MANUAL');
            $table->timestamp('sold_at');
            $table->timestamps();
        });

        Schema::create('pos_sale_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pos_sale_id')->constrained('pos_sales')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('product_name', 150);
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });

        if (! Schema::hasColumn('clients', 'pending_balance')) {
            Schema::table('clients', function (Blueprint $table): void {
                $table->decimal('pending_balance', 12, 2)->default(0);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_sale_items');
        Schema::dropIfExists('pos_sales');

        if (Schema::hasColumn('clients', 'pending_balance')) {
            Schema::table('clients', function (Blueprint $table): void {
                $table->dropColumn('pending_balance');
            });
        }
    }
};
