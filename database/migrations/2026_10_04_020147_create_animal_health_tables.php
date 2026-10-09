<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->date('record_date');
            $table->string('record_type', 30)->default('observation');
            $table->string('severity', 20)->nullable();
            $table->text('symptoms')->nullable()->comment('Gejala atau observasi lapangan');
            $table->text('diagnosis')->nullable()->comment('Diagnosis oleh tenaga medis, bukan hasil inferensi sistem');
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('open');
            $table->date('follow_up_date')->nullable();
            $table->date('resolved_date')->nullable();
            $table->decimal('cost', 15, 2)->default(0);
            $table->foreignId('veterinarian_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['animal_id', 'record_date']);
            $table->index('status');
        });

        Schema::create('vaccinations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->string('vaccine_name');
            $table->string('batch_number')->nullable();
            $table->date('administered_date');
            $table->date('next_due_date')->nullable();
            $table->string('dose')->nullable();
            $table->string('route')->nullable();
            $table->decimal('cost', 15, 2)->default(0);
            $table->string('administered_by')->nullable();
            $table->foreignId('veterinarian_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['next_due_date']);
            $table->index(['animal_id', 'administered_date']);
        });

        Schema::create('treatments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('health_record_id')->nullable()->constrained()->nullOnDelete();
            $table->string('medication_name');
            $table->string('dosage')->nullable();
            $table->string('route')->nullable();
            $table->string('frequency')->nullable();
            $table->date('treatment_date');
            $table->date('end_date')->nullable();
            $table->unsignedSmallInteger('withdrawal_period_days')->nullable()->comment('Masa tunggu sebelum susu dapat dikonsumsi');
            $table->decimal('cost', 15, 2)->default(0);
            $table->foreignId('veterinarian_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['animal_id', 'treatment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatments');
        Schema::dropIfExists('vaccinations');
        Schema::dropIfExists('health_records');
    }
};
