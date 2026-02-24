<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $managerRole = Role::firstOrCreate(['name' => 'manager']);
        $tenantRole = Role::firstOrCreate(['name' => 'tenant']);

        // Create Admin User
        User::firstOrCreate(
            ['email' => 'admin@okaro.local'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('ChangeMe123!'),
                'role_id' => $adminRole->id,
                'is_active' => true,
            ]
        );

        // You can add more seeders here for Buildings, Units, etc. if needed
    }
}
