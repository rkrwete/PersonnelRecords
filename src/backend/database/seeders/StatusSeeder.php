<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder {
    public function run(): void {
        $statuses = [
            ['name' => 'Налицо'],
            ['name' => 'Наряд'],
            ['name' => 'Лазарет'],
            ['name' => 'Госпиталь'],
            ['name' => 'Отпуск'],
            ['name' => 'Командировка'],
            ['name' => 'Увольнение'],
            ['name' => 'Суточное увольнение'],
            ['name' => 'Арест'],
            ['name' => 'СОЧ'],
            ['name' => 'Прочее'],
        ];

        foreach ($statuses as $status) {
            Status::updateOrCreate(['name' => $status['name']], $status);
        }
    }
}
