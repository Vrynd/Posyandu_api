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
        Schema::create('kunjungan_dewasa', function (Blueprint $table) {
            $table->id()->autoIncrement()->primary();
            $table->decimal('tinggi_badan', 5, 1)->nullable();
            $table->enum('imt', ['sangat_kurus', 'kurus', 'normal', 'gemuk', 'obesitas'])->nullable();
            $table->decimal('lingkar_perut', 5, 1)->nullable();
            $table->string('tekanan_darah')->nullable();
            $table->decimal('gula_darah', 5, 1)->nullable();
            $table->decimal('asam_urat', 4, 1)->nullable();
            $table->decimal('kolesterol', 5, 1)->nullable();
            $table->enum('tes_mata', ['normal', 'gangguan'])->nullable();
            $table->enum('tes_telinga', ['normal', 'gangguan'])->nullable();
            $table->json('skrining_tbc')->nullable();
            // Produktif specific
            $table->json('skrining_puma')->nullable();
            $table->integer('jumlah_skor_puma')->nullable();
            $table->string('alat_kontrasepsi')->nullable();
            // Lansia specific
            $table->json('adl')->nullable();
            $table->integer('jumlah_skor_adl')->nullable();
            $table->enum('tingkat_kemandirian', ['mandiri', 'ringan', 'sedang', 'berat', 'total'])->nullable();
            $table->json('edukasi')->nullable();
            $table->foreign('id')
                  ->references('id')
                  ->on('kunjungan')
                  ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kunjungan_dewasa');
    }
};
