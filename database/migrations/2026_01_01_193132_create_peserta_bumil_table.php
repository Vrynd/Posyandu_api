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
        Schema::create('peserta_bumil', function (Blueprint $table) {
            $table->foreignId('peserta_id')->primary()->constrained('peserta')->onDelete('cascade');
            $table->string('nama_suami')->nullable();
            $table->integer('hamil_anak_ke')->nullable();
            $table->string('jarak_anak')->nullable();
            $table->decimal('bb_sebelum_hamil', 4, 1)->nullable();
            $table->decimal('tinggi_badan', 5, 1)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peserta_bumil');
    }
};
