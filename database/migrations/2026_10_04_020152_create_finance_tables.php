<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_transactions', function (Blueprint $table): void {
            $table->id();
            $table->string('transaction_number', 30)->unique();
            $table->date('transaction_date');
            $table->string('direction', 10)->comment('in|out');
            $table->string('category', 40)->comment('customer_receipt|supplier_payment|expense|other_income|other_out|owner_draw|loan');
            $table->decimal('amount', 18, 2);
            $table->string('description');
            $table->string('method', 30)->default('transfer');
            $table->string('reference_number')->nullable();
            $table->nullableMorphs('source');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['transaction_date', 'direction']);
            $table->index('category');
        });

        Schema::create('supplier_payments', function (Blueprint $table): void {
            $table->id();
            $table->string('payment_number', 30)->unique();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('goods_receipt_id')->nullable()->constrained()->nullOnDelete();
            $table->date('payment_date');
            $table->decimal('amount', 18, 2);
            $table->string('method', 30)->default('transfer');
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['supplier_id', 'payment_date']);
            $table->index('goods_receipt_id');
        });

        Schema::create('expenses', function (Blueprint $table): void {
            $table->id();
            $table->string('expense_number', 30)->unique();
            $table->date('expense_date');
            $table->string('category', 40)->default('other');
            $table->decimal('amount', 18, 2);
            $table->string('description');
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('vendor_name')->nullable();
            $table->string('reference')->nullable();
            $table->string('payment_method', 30)->default('cash');
            $table->foreignId('cash_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['expense_date', 'category']);
        });

        Schema::create('incomes', function (Blueprint $table): void {
            $table->id();
            $table->string('income_number', 30)->unique();
            $table->date('income_date');
            $table->string('category', 40)->default('other');
            $table->decimal('amount', 18, 2);
            $table->string('description');
            $table->string('source_name')->nullable();
            $table->string('reference')->nullable();
            $table->string('payment_method', 30)->default('cash');
            $table->foreignId('cash_transaction_id')->nullable()->constrained('cash_transactions')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['income_date', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incomes');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('supplier_payments');
        Schema::dropIfExists('cash_transactions');
    }
};
