<?php

// database/seeders/UserSeeder.php
namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder {
    public function run(): void {
        $roleAdmin = Role::where('slug', 'admin')->first();
        $roleDrill = Role::where('slug', 'drill')->first();
        $roleMedic = Role::where('slug', 'medic')->first();
        $roleCmdr  = Role::where('slug', 'commander')->first();

        $unitAcademy = Unit::where('name', 'Военная академия')->first();
        $unitDept31  = Unit::where('name', '31 кафедра')->first();
        $unitSciComp = Unit::where('name', 'Научная рота')->first();

        $users = [
            [
                'name'     => 'Администратор системы',
                'login'    => 'admin_system',
                'role_id'  => $roleAdmin->id,
                'unit_id'  => null,
            ],
            [
                'name'     => 'Начальник академии',
                'login'    => 'nvas',
                'role_id'  => $roleAdmin->id,
                'unit_id'  => $unitAcademy->id,
            ],
            [
                'name'     => 'Строевой отдел',
                'login'    => 'drill',
                'role_id'  => $roleDrill->id,
                'unit_id'  => $unitAcademy->id,
            ],
            [
                'name'     => 'Начальник мед. службы',
                'login'    => 'medic',
                'role_id'  => $roleMedic->id,
                'unit_id'  => $unitAcademy->id,
            ],
            [
                'name'     => 'Начальник 31 кафедры',
                'login'    => 'dept31',
                'role_id'  => $roleCmdr->id,
                'unit_id'  => $unitDept31->id,
            ],
            [
                'name'     => 'Командир научной роты',
                'login'    => 'science_roto',
                'role_id'  => $roleCmdr->id,
                'unit_id'  => $unitSciComp->id,
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['login' => $userData['login']],
                [
                    'name'     => $userData['name'],
                    'password' => Hash::make('password123'),
                    'role_id'  => $userData['role_id'],
                    'unit_id'  => $userData['unit_id'],
                ]
            );
        }
    }
}
