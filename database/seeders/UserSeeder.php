<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin
        User::updateOrCreate(
            ['email' => 'admin@siha.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        // Doctor
        User::updateOrCreate(
            ['email' => 'doctor@siha.com'],
            [
                'name' => 'Dr. Smith',
                'password' => Hash::make('password'),
                'role' => 'doctor',
            ]
        );

        // Staff
        User::updateOrCreate(
            ['email' => 'staff@siha.com'],
            [
                'name' => 'Sarah Staff',
                'password' => Hash::make('password'),
                'role' => 'staff',
            ]
        );
    }
}
