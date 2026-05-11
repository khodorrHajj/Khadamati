<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::insert([
            ['id' => 1, 'role' => 'admin'],
            ['id' => 2, 'role' => 'municipality'],
            ['id' => 3, 'role' => 'citizen'],
        ]);
    }
}
