<?php

namespace Database\Seeders;

use App\Models\Unit;
use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // ==================== НАУЧНАЯ РОТА ====================
        $scientificCompany = Unit::where('name', 'Научная рота')->first();
        
        if ($scientificCompany) {
            $this->command->info('Научная рота найдена с ID: ' . $scientificCompany->id);
            
            // Создаем категории для научной роты
            $categories = [
                ['name' => 'Постоянный состав', 'order' => 1],
                ['name' => 'Переменный состав', 'order' => 2],
            ];
            
            foreach ($categories as $catData) {
                Category::updateOrCreate(
                    [
                        'unit_id' => $scientificCompany->id,
                        'name' => $catData['name']
                    ],
                    [
                        'unit_id' => $scientificCompany->id,
                        'name' => $catData['name']
                    ]
                );
                $this->command->info('Создана категория: ' . $catData['name']);
            }
        } else {
            $this->command->error('Научная рота не найдена!');
        }
        
        // ==================== 31 КАФЕДРА ====================
        $department31 = Unit::where('name', '31 кафедра')->first();
        
        if ($department31) {
            $this->command->info('31 кафедра найдена с ID: ' . $department31->id);
            
            // Создаем категории для 31 кафедры
            $categories = [
                ['name' => 'Постоянный состав', 'order' => 1],
                ['name' => 'Преподавательский состав ВС', 'order' => 2],
                ['name' => 'Преподавательский состав ГП', 'order' => 3],
                ['name' => 'Инженерно-технический состав', 'order' => 4],
                ['name' => 'Переменный состав', 'order' => 5],
            ];
            
            foreach ($categories as $catData) {
                Category::updateOrCreate(
                    [
                        'unit_id' => $department31->id,
                        'name' => $catData['name']
                    ],
                    [
                        'unit_id' => $department31->id,
                        'name' => $catData['name']
                    ]
                );
                $this->command->info('Создана категория: ' . $catData['name']);
            }
        } else {
            $this->command->error('31 кафедра не найдена!');
        }
        
        $this->command->info('=====================================');
        $this->command->info('Категории успешно созданы!');
        $this->command->info('=====================================');
    }
}