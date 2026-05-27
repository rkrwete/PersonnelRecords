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

//Сидер с тестовыми данными

class Department31PersonnelSeeder extends Seeder
{
    public function run(): void
    {
        // ==================== ПОЛУЧАЕМ 31 КАФЕДРУ ====================
        $department = Unit::where('name', '31 кафедра')->first();
        
        if (!$department) {
            $this->command->error('31 кафедра не найдена!');
            $this->command->info('Пожалуйста, сначала создайте подразделение "31 кафедра"');
            return;
        }
        
        $this->command->info('31 кафедра найдена с ID: ' . $department->id);
        
        // ==================== ПОЛУЧАЕМ КАТЕГОРИИ ====================
        $categories = [
            'Постоянный состав' => Category::where('unit_id', $department->id)->where('name', 'Постоянный состав')->first(),
            'Преподавательский состав ВС' => Category::where('unit_id', $department->id)->where('name', 'Преподавательский состав ВС')->first(),
            'Преподавательский состав ГП' => Category::where('unit_id', $department->id)->where('name', 'Преподавательский состав ГП')->first(),
            'Инженерно-технический состав' => Category::where('unit_id', $department->id)->where('name', 'Инженерно-технический состав')->first(),
            'Переменный состав' => Category::where('unit_id', $department->id)->where('name', 'Переменный состав')->first(),
        ];
        
        // Проверяем, что все категории существуют
        foreach ($categories as $name => $category) {
            if (!$category) {
                $this->command->error('Категория "' . $name . '" не найдена!');
                $this->command->info('Сначала запустите CategorySeeder');
                return;
            }
        }
        
        $this->command->info('Категории найдены');
        
        // ==================== СОЗДАЕМ ДОЛЖНОСТИ ====================
        
        // 1. Постоянный состав
        $positions = [
            'Постоянный состав' => [
                ['title' => 'Начальник кафедры', 'count' => 1],
                ['title' => 'Заместитель начальника кафедры', 'count' => 1],
                ['title' => 'Начальник лаборатории', 'count' => 1],
            ],
            'Преподавательский состав ВС' => [
                ['title' => 'Профессор', 'count' => 2],
                ['title' => 'Доцент', 'count' => 3],
                ['title' => 'Старший преподаватель', 'count' => 4],
                ['title' => 'Преподаватель', 'count' => 5],
            ],
            'Преподавательский состав ГП' => [
                ['title' => 'Профессор', 'count' => 1],
                ['title' => 'Доцент', 'count' => 2],
                ['title' => 'Старший преподаватель', 'count' => 2],
                ['title' => 'Преподаватель', 'count' => 3],
                ['title' => 'Ассистент', 'count' => 2],
            ],
            'Инженерно-технический состав' => [
                ['title' => 'Ведущий инженер', 'count' => 2],
                ['title' => 'Инженер 1 категории', 'count' => 3],
                ['title' => 'Инженер', 'count' => 4],
                ['title' => 'Техник', 'count' => 3],
                ['title' => 'Лаборант', 'count' => 2],
            ],
            'Переменный состав' => [
                ['title' => 'Адъюнкт', 'count' => 5],
                ['title' => 'Слушатель', 'count' => 10],
                ['title' => 'Стажёр', 'count' => 5],
            ],
        ];
        
        $createdPositions = [];
        
        foreach ($positions as $categoryName => $posList) {
            $category = $categories[$categoryName];
            foreach ($posList as $pos) {
                $position = Position::updateOrCreate(
                    [
                        'category_id' => $category->id,
                        'title' => $pos['title']
                    ],
                    [
                        'category_id' => $category->id,
                        'title' => $pos['title'],
                        'count' => $pos['count']
                    ]
                );
                $createdPositions[$categoryName][$pos['title']] = $position;
                $this->command->info('Создана должность: ' . $pos['title'] . ' (' . $categoryName . ')');
            }
        }
        
        // ==================== ПОЛУЧАЕМ ЗВАНИЯ ====================
        $ranks = [
            'colonel' => Rank::where('name', 'Полковник')->first(),
            'lieutenant_colonel' => Rank::where('name', 'Подполковник')->first(),
            'major' => Rank::where('name', 'Майор')->first(),
            'captain' => Rank::where('name', 'Капитан')->first(),
            'lieutenant' => Rank::where('name', 'Лейтенант')->first(),
            'senior_lieutenant' => Rank::where('name', 'Старший лейтенант')->first(),
            'praporshchik' => Rank::where('name', 'Прапорщик')->first(),
            'senior_praporshchik' => Rank::where('name', 'Старший прапорщик')->first(),
            'sergeant' => Rank::where('name', 'Сержант')->first(),
            'senior_sergeant' => Rank::where('name', 'Старший сержант')->first(),
            'no_rank' => Rank::where('name', 'Отсутствует')->first(),
        ];
        
        // Получаем статус "Налицо" (id=1)
        $presentStatus = Status::where('id', 1)->first();
        if (!$presentStatus) {
            $presentStatus = Status::where('name', 'Налицо')->first();
        }
        
        // Получаем пользователя по умолчанию
        $defaultUser = User::first();
        $defaultUserId = $defaultUser ? $defaultUser->id : 1;
        
        // ==================== СОЗДАЕМ ЛЮДЕЙ ====================
        
        $personnelList = [];
        
        // 1. Постоянный состав (3 человека)
        $personnelList[] = [
            'first_name' => 'Сергей',
            'last_name' => 'Михайлов',
            'middle_name' => 'Николаевич',
            'category' => 'Постоянный состав',
            'position' => 'Начальник кафедры',
            'rank_id' => $ranks['colonel']->id,
            'note' => 'Начальник 31 кафедры, полковник',
        ];
        
        $personnelList[] = [
            'first_name' => 'Андрей',
            'last_name' => 'Петров',
            'middle_name' => 'Викторович',
            'category' => 'Постоянный состав',
            'position' => 'Заместитель начальника кафедры',
            'rank_id' => $ranks['lieutenant_colonel']->id,
            'note' => 'Зам. начальника кафедры, подполковник',
        ];
        
        $personnelList[] = [
            'first_name' => 'Владимир',
            'last_name' => 'Сидоров',
            'middle_name' => 'Алексеевич',
            'category' => 'Постоянный состав',
            'position' => 'Начальник лаборатории',
            'rank_id' => $ranks['major']->id,
            'note' => 'Начальник лаборатории, майор',
        ];
        
        // 2. Преподавательский состав ВС (14 человек)
        $vsTeachers = [
            ['Профессор', 'Александр', 'Козлов', 'Иванович', 'colonel', 'Профессор, доктор наук'],
            ['Профессор', 'Борис', 'Морозов', 'Петрович', 'colonel', 'Профессор, доктор наук'],
            ['Доцент', 'Дмитрий', 'Волков', 'Сергеевич', 'lieutenant_colonel', 'Доцент, кандидат наук'],
            ['Доцент', 'Игорь', 'Зайцев', 'Андреевич', 'lieutenant_colonel', 'Доцент, кандидат наук'],
            ['Доцент', 'Павел', 'Соколов', 'Михайлович', 'lieutenant_colonel', 'Доцент, кандидат наук'],
            ['Старший преподаватель', 'Максим', 'Лебедев', 'Дмитриевич', 'major', 'Старший преподаватель'],
            ['Старший преподаватель', 'Роман', 'Новиков', 'Владимирович', 'major', 'Старший преподаватель'],
            ['Старший преподаватель', 'Олег', 'Федоров', 'Алексеевич', 'major', 'Старший преподаватель'],
            ['Старший преподаватель', 'Артем', 'Степанов', 'Игоревич', 'major', 'Старший преподаватель'],
            ['Преподаватель', 'Николай', 'Орлов', 'Васильевич', 'captain', 'Преподаватель'],
            ['Преподаватель', 'Евгений', 'Егоров', 'Андреевич', 'captain', 'Преподаватель'],
            ['Преподаватель', 'Василий', 'Соловьев', 'Петрович', 'captain', 'Преподаватель'],
            ['Преподаватель', 'Матвей', 'Карпов', 'Сергеевич', 'captain', 'Преподаватель'],
            ['Преподаватель', 'Григорий', 'Белов', 'Николаевич', 'captain', 'Преподаватель'],
        ];
        
        foreach ($vsTeachers as $teacher) {
            $personnelList[] = [
                'first_name' => $teacher[1],
                'last_name' => $teacher[2],
                'middle_name' => $teacher[3],
                'category' => 'Преподавательский состав ВС',
                'position' => $teacher[0],
                'rank_id' => $ranks[$teacher[4]]->id,
                'note' => $teacher[5],
            ];
        }
        
        // 3. Преподавательский состав ГП (10 человек)
        $gpTeachers = [
            ['Профессор', 'Сергей', 'Иванов', 'Петрович', 'lieutenant_colonel', 'Профессор'],
            ['Доцент', 'Алексей', 'Кузнецов', 'Александрович', 'major', 'Доцент'],
            ['Доцент', 'Виталий', 'Титов', 'Владимирович', 'major', 'Доцент'],
            ['Старший преподаватель', 'Егор', 'Михайлов', 'Игоревич', 'captain', 'Старший преподаватель'],
            ['Старший преподаватель', 'Кирилл', 'Андреев', 'Сергеевич', 'captain', 'Старший преподаватель'],
            ['Преподаватель', 'Антон', 'Васильев', 'Николаевич', 'senior_lieutenant', 'Преподаватель'],
            ['Преподаватель', 'Юрий', 'Семенов', 'Алексеевич', 'senior_lieutenant', 'Преподаватель'],
            ['Преподаватель', 'Станислав', 'Мельников', 'Владимирович', 'senior_lieutenant', 'Преподаватель'],
            ['Ассистент', 'Анатолий', 'Фомин', 'Петрович', 'lieutenant', 'Ассистент'],
            ['Ассистент', 'Валентин', 'Жуков', 'Иванович', 'lieutenant', 'Ассистент'],
        ];
        
        foreach ($gpTeachers as $teacher) {
            $personnelList[] = [
                'first_name' => $teacher[1],
                'last_name' => $teacher[2],
                'middle_name' => $teacher[3],
                'category' => 'Преподавательский состав ГП',
                'position' => $teacher[0],
                'rank_id' => $ranks[$teacher[4]]->id,
                'note' => $teacher[5],
            ];
        }
        
        // 4. Инженерно-технический состав (14 человек)
        $technicalStaff = [
            ['Ведущий инженер', 'Константин', 'Лавров', 'Андреевич', 'praporshchik', 'Ведущий инженер'],
            ['Ведущий инженер', 'Даниил', 'Баранов', 'Сергеевич', 'praporshchik', 'Ведущий инженер'],
            ['Инженер 1 категории', 'Вячеслав', 'Исаев', 'Михайлович', 'senior_praporshchik', 'Инженер 1 категории'],
            ['Инженер 1 категории', 'Федор', 'Саввин', 'Иванович', 'senior_praporshchik', 'Инженер 1 категории'],
            ['Инженер 1 категории', 'Александр', 'Блинов', 'Петрович', 'senior_praporshchik', 'Инженер 1 категории'],
            ['Инженер', 'Лев', 'Громов', 'Дмитриевич', 'sergeant', 'Инженер'],
            ['Инженер', 'Петр', 'Дьяконов', 'Алексеевич', 'sergeant', 'Инженер'],
            ['Инженер', 'Семен', 'Зимин', 'Владимирович', 'sergeant', 'Инженер'],
            ['Инженер', 'Захар', 'Кудрявцев', 'Игоревич', 'sergeant', 'Инженер'],
            ['Техник', 'Глеб', 'Лапин', 'Сергеевич', 'sergeant', 'Техник'],
            ['Техник', 'Родион', 'Рябов', 'Викторович', 'sergeant', 'Техник'],
            ['Техник', 'Елисей', 'Ширяев', 'Андреевич', 'sergeant', 'Техник'],
            ['Лаборант', 'Валерий', 'Горшков', 'Петрович', 'no_rank', 'Лаборант'],
            ['Лаборант', 'Кузьма', 'Малышев', 'Иванович', 'no_rank', 'Лаборант'],
        ];
        
        foreach ($technicalStaff as $staff) {
            $personnelList[] = [
                'first_name' => $staff[1],
                'last_name' => $staff[2],
                'middle_name' => $staff[3],
                'category' => 'Инженерно-технический состав',
                'position' => $staff[0],
                'rank_id' => $ranks[$staff[4]]->id,
                'note' => $staff[5],
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
            $position = $i < 5 ? 'Адъюнкт' : ($i < 15 ? 'Слушатель' : 'Стажёр');
            $rankKey = $i < 5 ? 'captain' : ($i < 10 ? 'lieutenant' : 'no_rank');
            
            $personnelList[] = [
                'first_name' => $firstNames[$i],
                'last_name' => $lastNames[$i],
                'middle_name' => $middleNames[$i],
                'category' => 'Переменный состав',
                'position' => $position,
                'rank_id' => $ranks[$rankKey]->id,
                'note' => $position . ' кафедры',
            ];
        }
        
        // ==================== СОХРАНЯЕМ ЛЮДЕЙ ====================
        
        $createdCount = 0;
        
        foreach ($personnelList as $person) {
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
        $this->command->info('Создано сотрудников: ' . $createdCount);
        $this->command->info('Всего сотрудников в кафедре: ' . $totalCount);
        $this->command->info('=====================================');
    }
}