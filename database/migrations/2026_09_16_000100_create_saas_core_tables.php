<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('slug')->unique();
            $table->string('status', 30)->default('trial')->index();
            $table->string('billing_email')->nullable();
            $table->string('timezone')->default('Europe/Berlin');
            $table->string('locale', 5)->default('de');
            $table->json('branding')->nullable();
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->timestamp('go_live_at')->nullable();
            $table->timestamps();
        });

        Schema::create('tenant_user', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('active')->index();
            $table->boolean('is_owner')->default(false);
            $table->timestamps();
            $table->primary(['tenant_id', 'user_id']);
        });

        Schema::create('saas_packages', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price_monthly', 10, 2)->nullable();
            $table->decimal('price_yearly', 10, 2)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->json('limits')->nullable();
            $table->timestamps();
        });

        Schema::create('features', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('feature_saas_package', function (Blueprint $table): void {
            $table->foreignId('feature_id')->constrained()->cascadeOnDelete();
            $table->foreignId('saas_package_id')->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            $table->string('limit_value')->nullable();
            $table->primary(['feature_id', 'saas_package_id']);
        });

        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('saas_package_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 30)->default('trial')->index();
            $table->string('billing_cycle', 20)->default('monthly');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('renews_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('external_reference')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('tenant_feature_overrides', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feature_id')->constrained()->cascadeOnDelete();
            $table->boolean('enabled');
            $table->string('limit_value')->nullable();
            $table->timestamps();
            $table->primary(['tenant_id', 'feature_id']);
        });

        Schema::create('tenant_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->longText('value')->nullable();
            $table->boolean('is_secret')->default(false);
            $table->timestamps();
            $table->unique(['tenant_id', 'key']);
        });

        Schema::create('system_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->boolean('is_secret')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('tenant_settings');
        Schema::dropIfExists('tenant_feature_overrides');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('feature_saas_package');
        Schema::dropIfExists('features');
        Schema::dropIfExists('saas_packages');
        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('tenants');
    }
};
