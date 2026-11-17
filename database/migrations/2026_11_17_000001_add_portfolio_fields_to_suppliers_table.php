<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (! Schema::hasColumn('suppliers', 'tax_id')) {
                $table->string('tax_id', 32)->nullable()->after('name');
            }
            if (! Schema::hasColumn('suppliers', 'address')) {
                $table->string('address', 255)->nullable()->after('email');
            }
            if (! Schema::hasColumn('suppliers', 'payment_terms')) {
                $table->string('payment_terms', 40)->nullable()->after('specialty');
            }
            if (! Schema::hasColumn('suppliers', 'status')) {
                $table->string('status', 20)->default('active')->after('payment_terms');
            }
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $columns = array_filter(
                ['tax_id', 'address', 'payment_terms', 'status'],
                fn (string $col): bool => Schema::hasColumn('suppliers', $col)
            );

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
