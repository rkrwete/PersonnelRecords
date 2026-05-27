<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder {
    public function run(): void {
        $statuses = [
            ['name' => 'Налицо'], //рассчитывается из в/г
            
            ['name' => 'Наряд'], // вне кафедры
            ['name' => 'Лазарет'],  // вне кафедры
            ['name' => 'Госпиталь'], // вне академии
            ['name' => 'Отпуск'],   // вне академии
            ['name' => 'Командировка'], // вне академии
            ['name' => 'Увольнение'],   // вне академии
            ['name' => 'Суточное увольнение'], // вне академии
            ['name' => 'Арест'],    // вне академии
            ['name' => 'СОЧ'],  // вне академии
            ['name' => 'Прочее'], // вне академии
            ['name' => '123 в/г'],  //налицо
            ['name' => '84 в/г'],   //налицо
            ['name' => '6 в/г'],    //налицо
            ['name' => 'УЦ (ИТ)'],  //налицо
            ['name' => 'Др. прич.'],    //налицо
            ['name' => 'Освобождены по болезни'], // вне кафедры
            ['name' => 'Гарнизон'], // вне академии
        ];

        foreach ($statuses as $status) {
            Status::updateOrCreate(['name' => $status['name']], $status);
        }
    }
}
