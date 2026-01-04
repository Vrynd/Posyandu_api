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
        Schema::create('kunjungan_bumil', function (Blueprint $table) {
            $table->id()->autoIncrement()->primary();
            $table->integer('umur_kehamilan')->nullable();
            $table->decimal('lila', 4, 1)->nullable();
            $table->string('tekanan_darah')->nullable();
            $table->json('skrining_tbc')->nullable();
            $table->boolean('tablet_darah')->default(false);
            $table->boolean('asi_eksklusif')->default(false);
            $table->boolean('mt_bumil_kek')->default(false);
            $table->boolean('kelas_bumil')->default(false);
            $table->json('penyuluhan')->nullable();
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
        Schema::dropIfExists('kunjungan_bumil');
    }
};
