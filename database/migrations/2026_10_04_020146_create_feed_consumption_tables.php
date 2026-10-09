<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feed_consumptions', function (Blueprint $table): void {
            $table->id();
            $table->string('consumption_number', 30)->unique();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('barn_id')->nullable()->constrained()->nullOnDelete();
            $table->date('consumption_date');
            $table->unsignedSmallInteger('animal_count')->default(0);
            $table->decimal('total_quantity', 15, 2)->default(0)->comment('Total kuantitas lintas item');
            $table->decimal('total_amount', 18, 2)->default(0)->comment('Nilai rupiah total seluruh item');
            $table->decimal('average_per_animal', 15, 4)->default(0);
            $table->boolean('allow_negative_stock')->default(false)->comment('Persetujuan khusus untuk stok negatif');
            $table->string('override_reason')->nullable();
            $table->foreignId('override_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['farm_id', 'consumption_date']);
        });

        Schema::create('feed_consumption_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('feed_consumption_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feed_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('inventory_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity', 15, 2);
            $table->string('unit', 20)->default('kg');
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('line_total', 18, 2)->default(0);
            $table->decimal('stock_before', 15, 2)->default(0);
            $table->decimal('stock_after', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('feed_item_id');
        });

        Schema::create('feed_consumption_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('feed_consumption_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feed_consumption_item_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('animal_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity', 15, 2)->default(0)
                ->comment('Atribusi operasional saja — TIDAK mengurangi stok kedua kali');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['feed_consumption_id', 'feed_consumption_item_id'], 'fca_consumption_item_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_consumption_allocations');
        Schema::dropIfExists('feed_consumption_items');
        Schema::dropIfExists('feed_consumptions');
    }
};
