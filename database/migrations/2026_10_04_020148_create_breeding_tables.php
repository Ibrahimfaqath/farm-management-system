<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('breeding_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('animal_id')->constrained('animals')->cascadeOnDelete();
            $table->string('record_type', 30)->default('insemination');
            $table->date('breeding_date');
            $table->foreignId('bull_id')->nullable()->constrained('animals')->nullOnDelete();
            $table->string('semen_code')->nullable();
            $table->string('semen_batch')->nullable();
            $table->string('inseminator')->nullable();
            $table->text('heat_signs')->nullable();
            $table->date('expected_next_heat_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['animal_id', 'breeding_date']);
        });

        Schema::create('pregnancy_checks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('animal_id')->constrained('animals')->cascadeOnDelete();
            $table->foreignId('breeding_record_id')->nullable()->constrained()->nullOnDelete();
            $table->date('check_date');
            $table->string('method', 30)->default('rectal');
            $table->string('result', 30)->default('unknown');
            $table->unsignedSmallInteger('estimated_gestation_days')->nullable();
            $table->date('expected_due_date')->nullable();
            $table->unsignedTinyInteger('fetus_count')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('veterinarian_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['animal_id', 'check_date']);
        });

        Schema::create('birth_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dam_id')->constrained('animals')->cascadeOnDelete();
            $table->foreignId('sire_id')->nullable()->constrained('animals')->nullOnDelete();
            $table->foreignId('animal_id')->nullable()->constrained('animals')->nullOnDelete()->comment('Anak yang lahir hidup');
            $table->foreignId('pregnancy_check_id')->nullable()->constrained()->nullOnDelete();
            $table->date('birth_date');
            $table->time('birth_time')->nullable();
            $table->string('birth_type', 30)->default('normal');
            $table->decimal('birth_weight_kg', 7, 2)->nullable();
            $table->string('colostrum_intake', 150)->nullable();
            $table->unsignedTinyInteger('calf_sex')->nullable();
            $table->text('assistance_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['dam_id', 'birth_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('birth_records');
        Schema::dropIfExists('pregnancy_checks');
        Schema::dropIfExists('breeding_records');
    }
};
