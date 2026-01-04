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
        Schema::create('kunjungan_remaja', function (Blueprint $table) {
            $table->id()->autoIncrement()->primary();
            $table->decimal('tinggi_badan', 5, 1)->nullable();
            $table->enum('imt', ['sangat_kurus', 'kurus', 'normal', 'gemuk', 'obesitas'])->nullable();
            $table->decimal('lingkar_perut', 5, 1)->nullable();
            $table->string('tekanan_darah')->nullable();
            $table->decimal('gula_darah', 5, 1)->nullable();
            $table->string('kadar_hb')->nullable();
            $table->json('skrining_tbc')->nullable();
            $table->json('skrining_mental')->nullable();
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
        Schema::dropIfExists('kunjungan_remaja');
    }
};
