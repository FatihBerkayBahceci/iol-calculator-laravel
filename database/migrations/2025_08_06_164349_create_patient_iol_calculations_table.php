<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('patient_iol_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('examination_id')->nullable()->constrained('patient_examinations')->onDelete('set null');
            $table->date('calculation_date');
            $table->enum('algorithm_used', [
                'srk_t', 'hoffer_q', 'holladay_1', 'holladay_2', 
                'haigis', 'barrett', 'olsen', 'kane'
            ])->comment('Kullanılan algoritma');
            $table->decimal('axial_length_right', 5, 2)->nullable()->comment('Sağ göz aksiyel uzunluk');
            $table->decimal('axial_length_left', 5, 2)->nullable()->comment('Sol göz aksiyel uzunluk');
            $table->decimal('k1_right', 5, 2)->nullable()->comment('Sağ göz K1');
            $table->decimal('k1_left', 5, 2)->nullable()->comment('Sol göz K1');
            $table->decimal('k2_right', 5, 2)->nullable()->comment('Sağ göz K2');
            $table->decimal('k2_left', 5, 2)->nullable()->comment('Sol göz K2');
            $table->decimal('acd_right', 4, 2)->nullable()->comment('Sağ göz ön kamara derinliği');
            $table->decimal('acd_left', 4, 2)->nullable()->comment('Sol göz ön kamara derinliği');
            $table->decimal('lens_constant', 4, 2)->nullable()->comment('Lens sabiti');
            $table->decimal('target_refraction', 4, 2)->nullable()->comment('Hedef refraksiyon');
            $table->decimal('calculated_iol_power_right', 4, 2)->nullable()->comment('Sağ göz hesaplanan IOL gücü');
            $table->decimal('calculated_iol_power_left', 4, 2)->nullable()->comment('Sol göz hesaplanan IOL gücü');
            $table->string('recommended_lens_model')->nullable()->comment('Önerilen lens modeli');
            $table->string('recommended_lens_manufacturer')->nullable()->comment('Önerilen lens üreticisi');
            $table->json('calculation_parameters')->nullable()->comment('Hesaplama parametreleri');
            $table->json('results_summary')->nullable()->comment('Sonuç özeti');
            $table->text('notes')->nullable()->comment('Notlar');
            $table->foreignId('calculated_by')->constrained('users')->onDelete('cascade');
            $table->boolean('is_verified')->default(false)->comment('Doğrulandı mı');
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable()->comment('Doğrulama tarihi');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['patient_id', 'calculation_date']);
            $table->index(['examination_id']);
            $table->index(['algorithm_used']);
            $table->index(['calculated_by']);
            $table->index(['is_verified']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_iol_calculations');
    }
};
