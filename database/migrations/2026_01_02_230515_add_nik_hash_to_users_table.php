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
        Schema::table('users', function (Blueprint $table) {
            // Add nik_hash column for lookups (since nik is encrypted)
            $table->string('nik_hash', 64)->nullable()->unique()->after('nik');

            // Change nik to text to accommodate encrypted value
            $table->text('nik')->nullable()->change();

            // Change phone_number to text to accommodate encrypted value
            $table->text('phone_number')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('nik_hash');
            $table->string('nik', 16)->nullable()->unique()->change();
            $table->string('phone_number', 20)->nullable()->change();
        });
    }
};
