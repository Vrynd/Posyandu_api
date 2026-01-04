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
            $table->enum('role', ['admin', 'kader'])->default('kader')->after('password');
            $table->string('nik', 16)->nullable()->after('role');
            $table->string('phone_number', 15)->nullable()->after('nik');
            $table->string('avatar_url')->nullable()->after('phone_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'nik', 'phone_number', 'avatar_url']);
        });
    }
};
