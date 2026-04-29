<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder {
    public function run(): void {
        $roles = [
            [
                'slug' => 'drill',
                'name' => 'Строевой отдел',
                'priority' => 3,
            ],
            [
                'slug' => 'medic',
                'name' => 'Мед. служба',
                'priority' => 2,
            ],
            [
                'slug' => 'commander',
                'name' => 'Командир подразделения',
                'priority' => 1,
            ],
            [
                'slug' => 'admin',
                'name' => 'Администратор системы',
                'priority' => 4,
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
