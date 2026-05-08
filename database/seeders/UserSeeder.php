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
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@siha.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        // Doctor
        User::create([
            'name' => 'Dr. Smith',
            'email' => 'doctor@siha.com',
            'password' => 'password',
            'role' => 'doctor',
        ]);

        // Staff
        User::create([
            'name' => 'Sarah Staff',
            'email' => 'staff@siha.com',
            'password' => 'password',
            'role' => 'staff',
        ]);
    }
}
