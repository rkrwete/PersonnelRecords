<?php

namespace Database\Seeders;

use App\Models\Personnel;
use App\Models\Unit;
use App\Models\Category;
use App\Models\Position;
use App\Models\Rank;
use App\Models\Status;
use App\Models\User;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        // ==================== ПОЛУЧАЕМ 31 КАФЕДРУ ====================
        $department = Unit::where('name', '31 кафедра')->first();
        
        if (!$department) {
            $this->command->error('31 кафедра не найдена!');
            return;
        }
        
        $this->command->info('31 кафедра найдена с ID: ' . $department->id);
        
        // ==================== СОЗДАЕМ КАТЕГОРИИ ====================
        $categories = [
            ['name' => 'Постоянный состав', 'order' => 1],
            ['name' => 'Преподавательский состав ВС', 'order' => 2],
            ['name' => 'Преподавательский состав ГП', 'order' => 3],
            ['name' => 'Инженерно-технический состав', 'order' => 4],
            ['name' => 'Переменный состав', 'order' => 5],
        ];
        
        $createdCategories = [];
        
        foreach ($categories as $catData) {
            $category = Category::updateOrCreate(
                [
                    'unit_id' => $department->id,
                    'name' => $catData['name']
                ],
                [
                    'unit_id' => $department->id,
                    'name' => $catData['name']
                ]
            );
            $createdCategories[$catData['name']] = $category;
            $this->command->info('Создана категория: ' . $category->name . ' (ID: ' . $category->id . ')');
        }
        
        // ==================== СОЗДАЕМ ДОЛЖНОСТИ ====================
        
        // Постоянный состав
        $permanentPositions = [
            ['title' => 'Начальник кафедры', 'count' => 1],
            ['title' => 'Заместитель начальника кафедры', 'count' => 1],
            ['title' => 'Начальник лаборатории', 'count' => 1],
        ];
        
        foreach ($permanentPositions as $pos) {
            Position::updateOrCreate(
                [
                    'category_id' => $createdCategories['Постоянный состав']->id,
                    'title' => $pos['title']
                ],
                [
                    'category_id' => $createdCategories['Постоянный состав']->id,
                    'title' => $pos['title'],
                    'count' => $pos['count']
                ]
            );
            $this->command->info('Создана должность: ' . $pos['title']);
        }
        
        // Преподавательский состав ВС
        $vsPositions = [
            ['title' => 'Профессор', 'count' => 2],
            ['title' => 'Доцент', 'count' => 3],
            ['title' => 'Старший преподаватель', 'count' => 4],
            ['title' => 'Преподаватель', 'count' => 5],
        ];
        
        foreach ($vsPositions as $pos) {
            Position::updateOrCreate(
                [
                    'category_id' => $createdCategories['Преподавательский состав ВС']->id,
                    'title' => $pos['title']
                ],
                [
                    'category_id' => $createdCategories['Преподавательский состав ВС']->id,
                    'title' => $pos['title'],
                    'count' => $pos['count']
                ]
            );
            $this->command->info('Создана должность: ' . $pos['title']);
        }
        
        // Преподавательский состав ГП
        $gpPositions = [
            ['title' => 'Профессор', 'count' => 1],
            ['title' => 'Доцент', 'count' => 2],
            ['title' => 'Старший преподаватель', 'count' => 2],
            ['title' => 'Преподаватель', 'count' => 3],
            ['title' => 'Ассистент', 'count' => 2],
        ];
        
        foreach ($gpPositions as $pos) {
            Position::updateOrCreate(
                [
                    'category_id' => $createdCategories['Преподавательский состав ГП']->id,
                    'title' => $pos['title']
                ],
                [
                    'category_id' => $createdCategories['Преподавательский состав ГП']->id,
                    'title' => $pos['title'],
                    'count' => $pos['count']
                ]
            );
            $this->command->info('Создана должность: ' . $pos['title']);
        }
        
        // Инженерно-технический состав
        $technicalPositions = [
            ['title' => 'Ведущий инженер', 'count' => 2],
            ['title' => 'Инженер 1 категории', 'count' => 3],
            ['title' => 'Инженер', 'count' => 4],
            ['title' => 'Техник', 'count' => 3],
            ['title' => 'Лаборант', 'count' => 2],
        ];
        
        foreach ($technicalPositions as $pos) {
            Position::updateOrCreate(
                [
                    'category_id' => $createdCategories['Инженерно-технический состав']->id,
                    'title' => $pos['title']
                ],
                [
                    'category_id' => $createdCategories['Инженерно-технический состав']->id,
                    'title' => $pos['title'],
                    'count' => $pos['count']
                ]
            );
            $this->command->info('Создана должность: ' . $pos['title']);
        }
        
        // Переменный состав
        $temporaryPositions = [
            ['title' => 'Адъюнкт', 'count' => 5],
            ['title' => 'Слушатель', 'count' => 10],
            ['title' => 'Стажёр', 'count' => 5],
        ];
        
        foreach ($temporaryPositions as $pos) {
            Position::updateOrCreate(
                [
                    'category_id' => $createdCategories['Переменный состав']->id,
                    'title' => $pos['title']
                ],
                [
                    'category_id' => $createdCategories['Переменный состав']->id,
                    'title' => $pos['title'],
                    'count' => $pos['count']
                ]
            );
            $this->command->info('Создана должность: ' . $pos['title']);
        }
        
        // ==================== ПОЛУЧАЕМ ЗВАНИЯ ====================
        $colonelRank = Rank::where('name', 'Полковник')->first();
        $lieutenantColonelRank = Rank::where('name', 'Подполковник')->first();
        $majorRank = Rank::where('name', 'Майор')->first();
        $captainRank = Rank::where('name', 'Капитан')->first();
        $lieutenantRank = Rank::where('name', 'Лейтенант')->first();
        $seniorLieutenantRank = Rank::where('name', 'Старший лейтенант')->first();
        $praporshchikRank = Rank::where('name', 'Прапорщик')->first();
        $seniorPraporshchikRank = Rank::where('name', 'Старший прапорщик')->first();
        $sergeantRank = Rank::where('name', 'Сержант')->first();
        $seniorSergeantRank = Rank::where('name', 'Старший сержант')->first();
        $noRank = Rank::where('name', 'Отсутствует')->first();
        
        // Получаем статус "Налицо"
        $presentStatus = Status::where('name', 'Налицо')->first();
        
        // Получаем пользователя по умолчанию
        $defaultUser = User::first();
        $defaultUserId = $defaultUser ? $defaultUser->id : 1;
        
        // ==================== СОЗДАЕМ ЛЮДЕЙ ====================
        
        $personnel = [];
        
        // 1. Постоянный состав (3 человека)
        $personnel[] = [
            'first_name' => 'Сергей',
            'last_name' => 'Михайлов',
            'middle_name' => 'Николаевич',
            'category' => 'Постоянный состав',
            'position' => 'Начальник кафедры',
            'rank_id' => $colonelRank->id,
            'note' => 'Начальник 31 кафедры, полковник',
        ];
        
        $personnel[] = [
            'first_name' => 'Андрей',
            'last_name' => 'Петров',
            'middle_name' => 'Викторович',
            'category' => 'Постоянный состав',
            'position' => 'Заместитель начальника кафедры',
            'rank_id' => $lieutenantColonelRank->id,
            'note' => 'Зам. начальника кафедры, подполковник',
        ];
        
        $personnel[] = [
            'first_name' => 'Владимир',
            'last_name' => 'Сидоров',
            'middle_name' => 'Алексеевич',
            'category' => 'Постоянный состав',
            'position' => 'Начальник лаборатории',
            'rank_id' => $majorRank->id,
            'note' => 'Начальник лаборатории, майор',
        ];
        
        // 2. Преподавательский состав ВС (14 человек)
        $vsNames = [
            ['Профессор', 'Александр', 'Козлов', 'Иванович', $colonelRank->id, 'Профессор, доктор наук'],
            ['Профессор', 'Борис', 'Морозов', 'Петрович', $colonelRank->id, 'Профессор, доктор наук'],
            ['Доцент', 'Дмитрий', 'Волков', 'Сергеевич', $lieutenantColonelRank->id, 'Доцент, кандидат наук'],
            ['Доцент', 'Игорь', 'Зайцев', 'Андреевич', $lieutenantColonelRank->id, 'Доцент, кандидат наук'],
            ['Доцент', 'Павел', 'Соколов', 'Михайлович', $lieutenantColonelRank->id, 'Доцент, кандидат наук'],
            ['Старший преподаватель', 'Максим', 'Лебедев', 'Дмитриевич', $majorRank->id, 'Старший преподаватель'],
            ['Старший преподаватель', 'Роман', 'Новиков', 'Владимирович', $majorRank->id, 'Старший преподаватель'],
            ['Старший преподаватель', 'Олег', 'Федоров', 'Алексеевич', $majorRank->id, 'Старший преподаватель'],
            ['Старший преподаватель', 'Артем', 'Степанов', 'Игоревич', $majorRank->id, 'Старший преподаватель'],
            ['Преподаватель', 'Николай', 'Орлов', 'Васильевич', $captainRank->id, 'Преподаватель'],
            ['Преподаватель', 'Евгений', 'Егоров', 'Андреевич', $captainRank->id, 'Преподаватель'],
            ['Преподаватель', 'Василий', 'Соловьев', 'Петрович', $captainRank->id, 'Преподаватель'],
            ['Преподаватель', 'Матвей', 'Карпов', 'Сергеевич', $captainRank->id, 'Преподаватель'],
            ['Преподаватель', 'Григорий', 'Белов', 'Николаевич', $captainRank->id, 'Преподаватель'],
        ];
        
        foreach ($vsNames as $vs) {
            $personnel[] = [
                'first_name' => $vs[1],
                'last_name' => $vs[2],
                'middle_name' => $vs[3],
                'category' => 'Преподавательский состав ВС',
                'position' => $vs[0],
                'rank_id' => $vs[4],
                'note' => $vs[5],
            ];
        }
        
        // 3. Преподавательский состав ГП (10 человек)
        $gpNames = [
            ['Профессор', 'Сергей', 'Иванов', 'Петрович', $lieutenantColonelRank->id, 'Профессор'],
            ['Доцент', 'Алексей', 'Кузнецов', 'Александрович', $majorRank->id, 'Доцент'],
            ['Доцент', 'Виталий', 'Титов', 'Владимирович', $majorRank->id, 'Доцент'],
            ['Старший преподаватель', 'Егор', 'Михайлов', 'Игоревич', $captainRank->id, 'Старший преподаватель'],
            ['Старший преподаватель', 'Кирилл', 'Андреев', 'Сергеевич', $captainRank->id, 'Старший преподаватель'],
            ['Преподаватель', 'Антон', 'Васильев', 'Николаевич', $seniorLieutenantRank->id, 'Преподаватель'],
            ['Преподаватель', 'Юрий', 'Семенов', 'Алексеевич', $seniorLieutenantRank->id, 'Преподаватель'],
            ['Преподаватель', 'Станислав', 'Мельников', 'Владимирович', $seniorLieutenantRank->id, 'Преподаватель'],
            ['Ассистент', 'Анатолий', 'Фомин', 'Петрович', $lieutenantRank->id, 'Ассистент'],
            ['Ассистент', 'Валентин', 'Жуков', 'Иванович', $lieutenantRank->id, 'Ассистент'],
        ];
        
        foreach ($gpNames as $gp) {
            $personnel[] = [
                'first_name' => $gp[1],
                'last_name' => $gp[2],
                'middle_name' => $gp[3],
                'category' => 'Преподавательский состав ГП',
                'position' => $gp[0],
                'rank_id' => $gp[4],
                'note' => $gp[5],
            ];
        }
        
        // 4. Инженерно-технический состав (14 человек)
        $technicalNames = [
            ['Ведущий инженер', 'Константин', 'Лавров', 'Андреевич', $praporshchikRank->id, 'Ведущий инженер'],
            ['Ведущий инженер', 'Даниил', 'Баранов', 'Сергеевич', $praporshchikRank->id, 'Ведущий инженер'],
            ['Инженер 1 категории', 'Вячеслав', 'Исаев', 'Михайлович', $seniorPraporshchikRank->id, 'Инженер 1 категории'],
            ['Инженер 1 категории', 'Федор', 'Саввин', 'Иванович', $seniorPraporshchikRank->id, 'Инженер 1 категории'],
            ['Инженер 1 категории', 'Александр', 'Блинов', 'Петрович', $seniorPraporshchikRank->id, 'Инженер 1 категории'],
            ['Инженер', 'Лев', 'Громов', 'Дмитриевич', $sergeantRank->id, 'Инженер'],
            ['Инженер', 'Петр', 'Дьяконов', 'Алексеевич', $sergeantRank->id, 'Инженер'],
            ['Инженер', 'Семен', 'Зимин', 'Владимирович', $sergeantRank->id, 'Инженер'],
            ['Инженер', 'Захар', 'Кудрявцев', 'Игоревич', $sergeantRank->id, 'Инженер'],
            ['Техник', 'Глеб', 'Лапин', 'Сергеевич', $sergeantRank->id, 'Техник'],
            ['Техник', 'Родион', 'Рябов', 'Викторович', $sergeantRank->id, 'Техник'],
            ['Техник', 'Елисей', 'Ширяев', 'Андреевич', $sergeantRank->id, 'Техник'],
            ['Лаборант', 'Валерий', 'Горшков', 'Петрович', $noRank->id, 'Лаборант'],
            ['Лаборант', 'Кузьма', 'Малышев', 'Иванович', $noRank->id, 'Лаборант'],
        ];
        
        foreach ($technicalNames as $tech) {
            $personnel[] = [
                'first_name' => $tech[1],
                'last_name' => $tech[2],
                'middle_name' => $tech[3],
                'category' => 'Инженерно-технический состав',
                'position' => $tech[0],
                'rank_id' => $tech[4],
                'note' => $tech[5],
            ];
        }
        
        // 5. Переменный состав (20 человек)
        $firstNames = [
            'Александр', 'Борис', 'Владимир', 'Георгий', 'Денис', 'Евгений', 'Ждан', 'Зиновий',
            'Игнатий', 'Константин', 'Леонид', 'Марк', 'Никита', 'Орест', 'Платон', 'Ростислав',
            'Святослав', 'Тимофей', 'Устин', 'Филипп'
        ];
        
        $lastNames = [
            'Воронов', 'Гришин', 'Давыдов', 'Ермаков', 'Жилин', 'Завьялов', 'Ильин', 'Карташов',
            'Лукин', 'Маслов', 'Некрасов', 'Осипов', 'Поляков', 'Родионов', 'Савельев', 'Тихонов',
            'Уваров', 'Филиппов', 'Харитонов', 'Цветков'
        ];
        
        $middleNames = [
            'Александрович', 'Борисович', 'Владимирович', 'Георгиевич', 'Денисович', 'Евгеньевич',
            'Жданович', 'Зиновьевич', 'Игнатьевич', 'Константинович', 'Леонидович', 'Маркович',
            'Никитич', 'Орестович', 'Платонович', 'Ростиславович', 'Святославович', 'Тимофеевич',
            'Устинович', 'Филиппович'
        ];
        
        for ($i = 0; $i < 20; $i++) {
            $personnel[] = [
                'first_name' => $firstNames[$i % count($firstNames)],
                'last_name' => $lastNames[$i % count($lastNames)],
                'middle_name' => $middleNames[$i % count($middleNames)],
                'category' => 'Переменный состав',
                'position' => $i < 5 ? 'Адъюнкт' : ($i < 15 ? 'Слушатель' : 'Стажёр'),
                'rank_id' => $i < 5 ? $captainRank->id : ($i < 10 ? $lieutenantRank->id : $noRank->id),
                'note' => $i < 5 ? 'Адъюнкт кафедры' : ($i < 15 ? 'Слушатель' : 'Стажёр'),
            ];
        }
        
        // ==================== СОЗДАЕМ ЛЮДЕЙ В БАЗЕ ====================
        
        $createdCount = 0;
        
        foreach ($personnel as $person) {
            $position = Position::where('title', $person['position'])
                ->whereHas('category', function ($q) use ($person) {
                    $q->where('name', $person['category']);
                })
                ->first();
            
            if (!$position) {
                $this->command->warn('Должность не найдена: ' . $person['position'] . ' (' . $person['category'] . ')');
                continue;
            }
            
            Personnel::updateOrCreate(
                [
                    'first_name' => $person['first_name'],
                    'last_name' => $person['last_name'],
                    'middle_name' => $person['middle_name'],
                    'unit_id' => $department->id,
                ],
                [
                    'unit_id' => $department->id,
                    'position_id' => $position->id,
                    'rank_id' => $person['rank_id'],
                    'current_status_id' => $presentStatus->id,
                    'status_set_by_user_id' => $defaultUserId,
                    'note' => $person['note'],
                ]
            );
            $createdCount++;
        }
        
        // ==================== ИТОГИ ====================
        $totalCount = Personnel::where('unit_id', $department->id)->count();
        
        $this->command->info('=====================================');
        $this->command->info('31 кафедра успешно заполнена!');
        $this->command->info('Категории: 5');
        $this->command->info('Должности: ' . Position::whereHas('category', function($q) use ($department) {
            $q->where('unit_id', $department->id);
        })->count());
        $this->command->info('Создано сотрудников: ' . $createdCount);
        $this->command->info('Всего сотрудников в кафедре: ' . $totalCount);
        $this->command->info('=====================================');
    }
}