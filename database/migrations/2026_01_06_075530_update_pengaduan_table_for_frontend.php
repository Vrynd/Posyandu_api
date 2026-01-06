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
        Schema::table('pengaduan', function (Blueprint $table) {
            // Drop old columns
            $table->dropColumn(['foto_url', 'balasan']);
        });

        Schema::table('pengaduan', function (Blueprint $table) {
            // Update kategori enum
            $table->enum('kategori_new', ['error', 'tampilan', 'data', 'performa', 'lainnya'])->after('user_id');

            // Add prioritas
            $table->enum('prioritas', ['rendah', 'sedang', 'tinggi'])->default('sedang')->after('kategori_new');

            // Add new fields
            $table->text('langkah_reproduksi')->nullable()->after('deskripsi');
            $table->text('browser_info')->nullable()->after('langkah_reproduksi');

            // Soft deletes
            $table->softDeletes();
        });

        // Update status enum values
        Schema::table('pengaduan', function (Blueprint $table) {
            $table->enum('status_new', ['pending', 'in_progress', 'resolved', 'rejected'])->default('pending')->after('browser_info');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengaduan', function (Blueprint $table) {
            $table->dropColumn(['kategori_new', 'prioritas', 'langkah_reproduksi', 'browser_info', 'status_new']);
            $table->dropSoftDeletes();

            // Restore old columns
            $table->string('foto_url')->nullable();
            $table->text('balasan')->nullable();
        });
    }
};
