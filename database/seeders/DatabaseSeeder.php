<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@jmsoneit.com',
            'password' => Hash::make('P@ssw0rd123'),
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'David Villondo',
            'email' => 'davidvillondo@jmsoneit.com',
            'password' => Hash::make('dasai2123'),
            'role' => 'it_support',
        ]);

        User::create([
            'name' => 'Staff Employee',
            'email' => 'staff@company.com',
            'password' => Hash::make('password'),
            'role' => 'staff',
        ]);
    }
}
