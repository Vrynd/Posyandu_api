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
        Schema::create('peserta_remaja', function (Blueprint $table) {
            $table->foreignId('peserta_id')->primary()->constrained('peserta')->onDelete('cascade');
            $table->string('nama_ortu')->nullable();
            $table->json('riwayat_keluarga')->nullable();
            $table->json('perilaku_berisiko')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peserta_remaja');
    }
};
