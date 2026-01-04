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
        Schema::create('peserta_dewasa', function (Blueprint $table) {
            $table->foreignId('peserta_id')->primary()->constrained('peserta')->onDelete('cascade');
            $table->string('pekerjaan')->nullable();
            $table->string('status_perkawinan')->nullable();
            $table->json('riwayat_diri')->nullable();
            $table->boolean('merokok')->default(false);
            $table->boolean('konsumsi_gula')->default(false);
            $table->boolean('konsumsi_garam')->default(false);
            $table->boolean('konsumsi_lemak')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peserta_dewasa');
    }
};
