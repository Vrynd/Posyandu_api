<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Seed the admin user.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@posyandu.id'],
            [
                'name' => 'Admin Posyandu',
                'email' => 'admin@posyandu.id',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'nik' => '1234567890123456',
                'nik_hash' => User::hashNik('1234567890123456'),
                'phone_number' => '081234567890',
            ]
        );
    }
}
