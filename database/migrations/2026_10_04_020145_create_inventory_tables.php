<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipts', function (Blueprint $table): void {
            $table->id();
            $table->string('receipt_number', 30)->unique();
            $table->foreignId('purchase_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->date('received_date');
            $table->string('status', 20)->default('posted');
            $table->string('delivery_note_reference')->nullable();
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->text('quality_notes')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->index(['purchase_order_id', 'status']);
        });

        Schema::create('goods_receipt_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('feed_item_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity_received', 15, 2);
            $table->string('unit', 20)->default('kg');
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('line_total', 18, 2)->default(0);
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('quality_grade')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['feed_item_id', 'goods_receipt_id']);
        });

        Schema::create('inventory_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('feed_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('goods_receipt_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('batch_code', 40);
            $table->string('unit', 20)->default('kg');
            $table->date('received_date');
            $table->decimal('original_quantity', 15, 2);
            $table->decimal('quantity_remaining', 15, 2)->default(0);
            $table->date('expiry_date')->nullable();
            $table->string('storage_location')->nullable();
            $table->string('quality_grade')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['feed_item_id', 'batch_code']);
            $table->index(['feed_item_id', 'status']);
        });

        Schema::create('stock_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('feed_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('inventory_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 25);
            $table->decimal('quantity', 15, 2)->comment('Selalu positif; arah ditentukan oleh tipe');
            $table->string('unit', 20)->default('kg');
            $table->date('movement_date');
            $table->string('reference')->nullable()->comment('Nomor dokumen sumber');
            $table->nullableMorphs('source');
            $table->string('reason')->nullable();
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['feed_item_id', 'movement_date']);
            $table->index(['type', 'movement_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('inventory_batches');
        Schema::dropIfExists('goods_receipt_items');
        Schema::dropIfExists('goods_receipts');
    }
};
