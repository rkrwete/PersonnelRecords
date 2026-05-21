<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MemorableDate;

class MemorableDatesSeeder extends Seeder
{
    public function run()
    {
        $dates = [
            ['name' => 'Новый год', 'date' => '01-01', 'description' => 'Новогодний праздник', 'color' => '#FF5252'],
            ['name' => 'День защитника Отечества', 'date' => '02-23', 'description' => 'Праздник мужчин', 'color' => '#FF9800'],
            ['name' => 'Международный женский день', 'date' => '03-08', 'description' => 'Праздник женщин', 'color' => '#E91E63'],
            ['name' => 'День Победы', 'date' => '05-09', 'description' => '77 лет победы', 'color' => '#4CAF50'],
            ['name' => 'День радио', 'date' => '05-07', 'description' => 'День рождения радио', 'color' => '#2196F3'],
            ['name' => 'День России', 'date' => '06-12', 'description' => 'День принятия декларации', 'color' => '#9C27B0'],
            ['name' => 'День Военной академии связи', 'date' => '11-08', 'description' => 'Основание академии', 'color' => '#FF9800'],
        ];

        foreach ($dates as $date) {
            MemorableDate::create($date);
        }
    }
}