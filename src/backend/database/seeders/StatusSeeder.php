<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder {
    public function run(): void {
        $statuses = [
            ['name' => 'Налицо'],
            ['name' => 'Лазарет'],
            ['name' => 'Госпиталь'],
            ['name' => 'В отпуске'],
            ['name' => 'В наряде'],
            ['name' => 'В командировке'],
            ['name' => 'Прочее'],
            ['name' => 'Отсутствует'],
            ['name' => 'Увольнениие'],
        ];

        foreach ($statuses as $status) {
            Status::updateOrCreate(['name' => $status['name']], $status);
        }
    }
}
