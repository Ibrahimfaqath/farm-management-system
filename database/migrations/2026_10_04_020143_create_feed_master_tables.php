<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feed_items', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('category', 30)->default('concentrate');
            $table->string('unit', 20)->default('kg');
            $table->decimal('price_per_unit', 15, 2)->default(0);
            $table->decimal('reorder_point', 15, 2)->default(0)->comment('Batas stok minimum sebelum peringatan');
            $table->decimal('reorder_quantity', 15, 2)->default(0);
            $table->string('storage_location')->nullable();
            $table->unsignedSmallInteger('shelf_life_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['category', 'is_active']);
        });

        Schema::create('suppliers', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('tax_number', 40)->nullable();
            $table->unsignedSmallInteger('payment_terms_days')->default(0);
            $table->string('category', 40)->default('feed')->comment('feed|equipment|other|mixed');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('feed_items');
    }
};
