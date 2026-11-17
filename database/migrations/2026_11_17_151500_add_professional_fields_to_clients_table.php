<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('client_type', 20)->default('person');
            $table->string('document_type', 20)->nullable();
            $table->string('document_number', 30)->nullable();
            $table->string('contact_name', 120)->nullable();
            $table->string('address')->nullable();
            $table->string('district', 120)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('preferred_contact', 20)->default('whatsapp');
            $table->string('source', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'client_type',
                'document_type',
                'document_number',
                'contact_name',
                'address',
                'district',
                'city',
                'preferred_contact',
                'source',
            ]);
        });
    }
};
