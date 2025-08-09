<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@donation.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        // Create regular user
        User::create([
            'name' => 'John Doe',
            'email' => 'user@donation.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);
    }
}