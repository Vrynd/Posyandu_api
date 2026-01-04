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
        Schema::create('peserta', function (Blueprint $table) {
            $table->id(); // Use ID as primary key
            $table->text('nik'); // Encrypted NIK
            $table->string('nik_hash')->index(); // Hash for searching
            $table->string('nama');
            $table->enum('kategori', ['bumil', 'balita', 'remaja', 'produktif', 'lansia']);
            $table->date('tanggal_lahir');
            $table->enum('jenis_kelamin', ['Laki-Laki', 'Perempuan']);
            $table->text('alamat')->nullable();
            $table->string('rt', 4)->nullable();
            $table->string('rw', 4)->nullable();
            $table->text('telepon')->nullable(); // Encrypted Phone
            $table->boolean('kepesertaan_bpjs')->default(false);
            $table->string('nomor_bpjs', 13)->nullable();
            $table->timestamps();

            $table->index('kategori');
            $table->index('nama');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peserta');
    }
};
