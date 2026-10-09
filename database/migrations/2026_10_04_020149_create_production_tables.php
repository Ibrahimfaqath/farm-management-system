<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('unit', 20)->default('liter');
            $table->decimal('default_price', 15, 2)->default(0);
            $table->string('category', 30)->default('milk')->comment('milk|byproduct|other');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('milk_productions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('animal_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('barn_id')->nullable()->constrained()->nullOnDelete();
            $table->date('production_date');
            $table->string('session', 20)->default('mixed');
            $table->decimal('liters', 10, 2)->default(0);
            $table->decimal('fat_percent', 5, 2)->nullable();
            $table->decimal('protein_percent', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['farm_id', 'production_date']);
            $table->index(['animal_id', 'production_date']);
            $table->index(['barn_id', 'production_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('milk_productions');
        Schema::dropIfExists('products');
    }
};
