<?php

namespace Database\Seeders;

use App\Models\Unit;
use App\Models\Category;
use App\Models\Position;
use Illuminate\Database\Seeder;

class ScientificSquadPositionSeeder extends Seeder
{
    public function run(): void
    {
        // Получаем научную роту
        $scientificCompany = Unit::where('name', 'Научная рота')->first();
        
        if (!$scientificCompany) {
            $this->command->error('Научная рота не найдена!');
            return;
        }
        
        $this->command->info('Найдена научная рота с ID: ' . $scientificCompany->id);
        
        // ==================== СОЗДАЕМ КАТЕГОРИИ ====================
        
        // Постоянный состав
        $permanentCategory = Category::updateOrCreate(
            [
                'unit_id' => $scientificCompany->id,
                'name' => 'Постоянный состав'
            ],
            [
                'unit_id' => $scientificCompany->id,
                'name' => 'Постоянный состав'
            ]
        );
        
        $this->command->info('Создана категория "Постоянный состав" с ID: ' . $permanentCategory->id);
        
        // Переменный состав
        $temporaryCategory = Category::updateOrCreate(
            [
                'unit_id' => $scientificCompany->id,
                'name' => 'Переменный состав'
            ],
            [
                'unit_id' => $scientificCompany->id,
                'name' => 'Переменный состав'
            ]
        );
        
        $this->command->info('Создана категория "Переменный состав" с ID: ' . $temporaryCategory->id);
        
        // ==================== СОЗДАЕМ ДОЛЖНОСТИ ====================
        
        // Должности постоянного состава
        $positions = [
            [
                'category_id' => $permanentCategory->id,
                'title' => 'Командир научной роты',
                'count' => 1,
            ],
            [
                'category_id' => $permanentCategory->id,
                'title' => 'Командир взвода',
                'count' => 3,
            ],
            [
                'category_id' => $permanentCategory->id,
                'title' => 'Заместитель командира взвода',
                'count' => 3,
            ],
            [
                'category_id' => $permanentCategory->id,
                'title' => 'Старшина роты',
                'count' => 1,
            ],
        ];
        
        foreach ($positions as $positionData) {
            $position = Position::updateOrCreate(
                [
                    'category_id' => $positionData['category_id'],
                    'title' => $positionData['title']
                ],
                $positionData
            );
            $this->command->info('Создана должность: ' . $position->title . ' (count: ' . $position->count . ')');
        }
        
        // Должности переменного состава
        $operatorPosition = Position::updateOrCreate(
            [
                'category_id' => $temporaryCategory->id,
                'title' => 'Оператор'
            ],
            [
                'category_id' => $temporaryCategory->id,
                'title' => 'Оператор',
                'count' => 40,
            ]
        );
        
        $this->command->info('Создана должность: ' . $operatorPosition->title . ' (count: ' . $operatorPosition->count . ')');
        
        $this->command->info('=====================================');
        $this->command->info('Структура научной роты создана:');
        $this->command->info('  - Постоянный состав: 4 должности');
        $this->command->info('  - Переменный состав: 1 должность');
        $this->command->info('=====================================');
    }
}