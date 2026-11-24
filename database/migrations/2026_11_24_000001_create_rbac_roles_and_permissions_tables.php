<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 80)->unique();
            $table->string('description', 255)->nullable();
            $table->string('color', 24)->default('fuchsia');
            $table->boolean('is_system')->default(false);
            $table->boolean('is_super_admin')->default(false);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 80)->unique();
            $table->string('module', 60);
            $table->string('name', 120);
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table): void {
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('role_id')->nullable()->after('email')->constrained('roles')->nullOnDelete();
            $table->string('phone', 32)->nullable()->after('role_id');
            $table->string('job_title', 80)->nullable()->after('phone');
            $table->boolean('active')->default(true)->after('job_title');
            $table->timestamp('last_login_at')->nullable()->after('active');
        });

        Role::syncSystemCatalog();
        $superAdminId = Role::query()->where('slug', 'super-admin')->value('id');

        if ($superAdminId) {
            DB::table('users')
                ->whereNull('role_id')
                ->update([
                    'role_id' => $superAdminId,
                    'active' => true,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('role_id');
            $table->dropColumn(['phone', 'job_title', 'active', 'last_login_at']);
        });

        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
