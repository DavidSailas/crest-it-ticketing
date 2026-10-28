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
            'username' => 'admin',
            'password' => Hash::make('P@ssw0rd123'),
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'David Villondo',
            'username' => 'davidvillondo',
            'password' => Hash::make('dasai2123'),
            'role' => 'it_support',
        ]);

        User::create([
            'name' => 'Staff Employee',
            'username' => 'staff',
            'password' => Hash::make('password'),
            'role' => 'staff',
        ]);
    }
}
