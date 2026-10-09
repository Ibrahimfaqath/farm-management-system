<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farms', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('village')->nullable();
            $table->string('district')->nullable();
            $table->string('regency')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('manager_name')->nullable();
            $table->decimal('area_hectares', 10, 2)->nullable();
            $table->date('established_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('barns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->string('type', 30)->default('dairy')->comment('dairy|calf|dry|quarantine|breeding|other');
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->decimal('area_m2', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['farm_id', 'code']);
        });

        Schema::create('breeds', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('species', 30)->default('cattle')->comment('cattle|buffalo|goat');
            $table->string('origin')->nullable();
            $table->decimal('average_milk_liters', 8, 2)->nullable();
            $table->unsignedSmallInteger('average_lactation_days')->nullable();
            $table->decimal('adult_weight_kg', 7, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('animals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('barn_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('breed_id')->constrained()->restrictOnDelete();
            $table->string('animal_code', 30)->unique();
            $table->string('name')->nullable();

            // Existence / lifecycle status. Sold, deceased and transferred animals are
            // retained so their history stays available.
            $table->string('status', 20)->default('active')
                ->comment('active|sold|deceased|transferred');

            // Independent status facets — an animal can be lactating, pregnant and
            // under treatment at the same time.
            $table->string('production_status', 20)->default('calf')
                ->comment('calf|heifer|lactating|dry|fattening|bull');

            // Denormalised read model of the latest breeding event.
            $table->string('breeding_status', 20)->default('open')
                ->comment('open|bred|pregnant|confirmed_pregnant|dry|not_applicable');

            $table->enum('sex', ['male', 'female'])->index();
            $table->date('date_of_birth')->nullable();

            $table->date('acquisition_date')->nullable();
            $table->string('acquisition_type', 20)->nullable()
                ->comment('birth|purchase|gift|transfer|unknown');
            $table->decimal('acquisition_cost', 15, 2)->default(0);
            $table->string('source_name')->nullable()->comment('Supplier, donor or previous farm');

            $table->foreignId('dam_id')->nullable()->constrained('animals')->nullOnDelete();
            $table->foreignId('sire_id')->nullable()->constrained('animals')->nullOnDelete();
            $table->foreignId('current_barn_id')->nullable()->constrained('barns')->nullOnDelete();

            $table->date('exit_date')->nullable();
            $table->string('exit_type', 20)->nullable()->comment('sold|deceased|transferred');
            $table->decimal('exit_value', 15, 2)->default(0);
            $table->string('exit_reference')->nullable();
            $table->text('exit_notes')->nullable();

            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['farm_id', 'status']);
            $table->index(['barn_id', 'production_status']);
            $table->index('breed_id');
            $table->index('breeding_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animals');
        Schema::dropIfExists('breeds');
        Schema::dropIfExists('barns');
        Schema::dropIfExists('farms');
    }
};
