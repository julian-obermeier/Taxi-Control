<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('employee_number', 50);
            $table->string('display_name');
            $table->string('phone')->nullable();
            $table->string('status', 30)->default('offline')->index();
            $table->json('qualifications')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'employee_number']);
            $table->index(['tenant_id', 'status', 'is_active']);
        });

        Schema::create('vehicles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('fleet_number', 50);
            $table->string('license_plate', 30);
            $table->string('vehicle_class', 50)->default('standard')->index();
            $table->unsignedSmallInteger('seats')->default(4);
            $table->json('equipment')->nullable();
            $table->string('status', 30)->default('available')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'fleet_number']);
            $table->unique(['tenant_id', 'license_plate']);
            $table->index(['tenant_id', 'vehicle_class', 'status']);
        });

        Schema::create('trip_types', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->unsignedSmallInteger('default_priority')->default(50);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'slug']);
        });

        Schema::create('trip_number_sequences', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
            $table->primary(['tenant_id', 'year']);
        });

        Schema::create('trips', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('order_number', 16);
            $table->foreignId('trip_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 40)->default('draft')->index();
            $table->string('dispatch_mode', 20)->default('manual')->index();
            $table->unsignedSmallInteger('priority')->default(50)->index();
            $table->string('passenger_name')->nullable();
            $table->string('passenger_phone')->nullable();
            $table->unsignedSmallInteger('passenger_count')->default(1);
            $table->string('vehicle_class', 50)->nullable();
            $table->json('requirements')->nullable();
            $table->text('pickup_address');
            $table->decimal('pickup_lat', 10, 7)->nullable();
            $table->decimal('pickup_lng', 10, 7)->nullable();
            $table->text('destination_address')->nullable();
            $table->decimal('destination_lat', 10, 7)->nullable();
            $table->decimal('destination_lng', 10, 7)->nullable();
            $table->timestamp('requested_at')->nullable()->index();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'order_number']);
            $table->index(['tenant_id', 'status', 'scheduled_at']);
            $table->index(['tenant_id', 'driver_id', 'status']);
            $table->index(['tenant_id', 'vehicle_id', 'status']);
        });

        Schema::create('trip_stops', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->string('stop_type', 20)->default('waypoint');
            $table->text('address');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->timestamp('planned_at')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('departed_at')->nullable();
            $table->unsignedInteger('waiting_seconds')->default(0);
            $table->boolean('passenger_boarding')->default(false);
            $table->boolean('passenger_alighting')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['trip_id', 'sequence']);
            $table->index(['tenant_id', 'trip_id']);
        });

        Schema::create('trip_state_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40);
            $table->string('source', 30)->default('web');
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['tenant_id', 'trip_id', 'created_at']);
        });

        Schema::create('driver_locations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->decimal('accuracy_m', 8, 2)->nullable();
            $table->decimal('speed_kmh', 8, 2)->nullable();
            $table->unsignedSmallInteger('heading')->nullable();
            $table->timestamp('recorded_at')->index();
            $table->timestamps();
            $table->index(['tenant_id', 'driver_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_locations');
        Schema::dropIfExists('trip_state_events');
        Schema::dropIfExists('trip_stops');
        Schema::dropIfExists('trips');
        Schema::dropIfExists('trip_number_sequences');
        Schema::dropIfExists('trip_types');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('drivers');
    }
};
