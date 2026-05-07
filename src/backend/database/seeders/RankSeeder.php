<?php

namespace Database\Seeders;

use App\Models\Rank;
use Illuminate\Database\Seeder;

class RankSeeder extends Seeder {
    public function run(): void {
        $ranks = [
            ['name' => 'Отсутствует', 'level' => 0],
            ['name' => 'Рядовой', 'level' => 1],
            ['name' => 'Ефрейтор', 'level' => 2],
            ['name' => 'Младший сержант', 'level' => 3],
            ['name' => 'Сержант', 'level' => 4],
            ['name' => 'Старший сержант', 'level' => 5],
            ['name' => 'Старшина', 'level' => 6],
            ['name' => 'Прапорщик', 'level' => 7],
            ['name' => 'Старший прапорщик', 'level' => 8],
            ['name' => 'Младший лейтенант', 'level' => 9],
            ['name' => 'Лейтенант', 'level' => 10],
            ['name' => 'Старший лейтенант', 'level' => 11],
            ['name' => 'Капитан', 'level' => 12],
            ['name' => 'Майор', 'level' => 13],
            ['name' => 'Подполковник', 'level' => 14],
            ['name' => 'Полковник', 'level' => 15],
            ['name' => 'Генерал-майор', 'level' => 16],
            ['name' => 'Генерал-лейтенант', 'level' => 17],
            ['name' => 'Генерал-полковник', 'level' => 18],
            ['name' => 'Генерал армии', 'level' => 19],
        ];

        foreach ($ranks as $rank) {
            Rank::updateOrCreate(['name' => $rank['name']], $rank);
        }
    }
}
