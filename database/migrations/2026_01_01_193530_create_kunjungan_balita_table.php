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
        Schema::create('kunjungan_balita', function (Blueprint $table) {
            $table->id()->autoIncrement()->primary();
            $table->integer('umur_bulan')->nullable();
            $table->enum('kesimpulan_bb', ['NAIK', 'TIDAK NAIK', 'BGM'])->nullable();
            $table->decimal('panjang_badan', 5, 1)->nullable();
            $table->decimal('lingkar_kepala', 4, 1)->nullable();
            $table->decimal('lingkar_lengan', 4, 1)->nullable();
            $table->json('skrining_tbc')->nullable();
            $table->json('balita_mendapatkan')->nullable();
            $table->json('edukasi_konseling')->nullable();
            $table->boolean('ada_gejala_sakit')->default(false);
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
        Schema::dropIfExists('kunjungan_balita');
    }
};
