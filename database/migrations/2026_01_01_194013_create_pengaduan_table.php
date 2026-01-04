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
        Schema::create('pengaduan', function (Blueprint $table) {
            $table->id()->autoIncrement()->primary();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('kategori', ['bug', 'saran', 'pertanyaan', 'lainnya']);
            $table->string('judul');
            $table->text('deskripsi');
            $table->string('foto_url')->nullable();
            $table->enum('status', ['pending', 'diproses', 'selesai'])->default('pending');
            $table->text('balasan')->nullable();
            $table->timestamps();

             // Indexes
            $table->index('user_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengaduan');
    }
};
