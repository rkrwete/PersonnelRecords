<?php

namespace Database\Seeders;

use App\Models\Personnel;
use App\Models\Unit;
use App\Models\Position;
use App\Models\Rank;
use App\Models\Status;
use App\Models\User;
use Illuminate\Database\Seeder;

class ScientificSquadSeeder extends Seeder
{
    public function run(): void
    {
        // Получаем научную роту
        $scientificCompany = Unit::where('name', 'Научная рота')->first();
        
        if (!$scientificCompany) {
            $this->command->error('Научная рота не найдена!');
            return;
        }
        
        $this->command->info('Научная рота найдена с ID: ' . $scientificCompany->id);
        
        // Получаем категории
        $permanentCategory = $scientificCompany->categories()->where('name', 'Постоянный состав')->first();
        $temporaryCategory = $scientificCompany->categories()->where('name', 'Переменный состав')->first();
        
        if (!$permanentCategory) {
            $this->command->error('Категория "Постоянный состав" не найдена!');
            return;
        }
        
        if (!$temporaryCategory) {
            $this->command->error('Категория "Переменный состав" не найдена!');
            return;
        }
        
        $this->command->info('Категории найдены:');
        $this->command->info('  - Постоянный состав ID: ' . $permanentCategory->id);
        $this->command->info('  - Переменный состав ID: ' . $temporaryCategory->id);
        
        // Получаем должности
        $commanderPosition = Position::where('category_id', $permanentCategory->id)
            ->where('title', 'Командир научной роты')->first();
        
        $platoonCommanderPosition = Position::where('category_id', $permanentCategory->id)
            ->where('title', 'Командир взвода')->first();
        
        $deputyCommanderPosition = Position::where('category_id', $permanentCategory->id)
            ->where('title', 'Заместитель командира взвода')->first();
        
        $seniorSergeantPosition = Position::where('category_id', $permanentCategory->id)
            ->where('title', 'Старшина роты')->first();
        
        $operatorPosition = Position::where('category_id', $temporaryCategory->id)
            ->where('title', 'Оператор')->first();
        
        // Получаем звания
        $captainRank = Rank::where('name', 'Капитан')->first();
        $lieutenantRank = Rank::where('name', 'Лейтенант')->first();
        $seniorLieutenantRank = Rank::where('name', 'Старший лейтенант')->first();
        $praporshchikRank = Rank::where('name', 'Прапорщик')->first();
        $sergeantRank = Rank::where('name', 'Сержант')->first();
        $seniorSergeantRank = Rank::where('name', 'Старший сержант')->first();
        $cadetRank = Rank::where('name', 'Рядовой')->first();
        
        if (!$cadetRank) {
            $cadetRank = Rank::where('name', 'Курсант')->first();
        }
        
        // Получаем статус "Налицо"
        $presentStatus = Status::where('name', 'Налицо')->first();
        
        if (!$presentStatus) {
            $this->command->error('Статус "Налицо" не найден!');
            return;
        }
        
        // Получаем пользователя по умолчанию (например, администратора)
        $defaultUser = User::first();
        if (!$defaultUser) {
            $this->command->warn('Пользователь не найден, создаем временного...');
            $defaultUser = User::create([
                'name' => 'System',
                'login' => 'system',
                'password' => bcrypt('password'),
                'role_id' => 1,
                'unit_id' => null,
            ]);
        }
        $defaultUserId = $defaultUser->id;
        
        $this->command->info('Должности найдены');
        $this->command->info('Звания и статус найдены');
        $this->command->info('Пользователь для status_set_by_user_id: ' . $defaultUserId);
        
        // ==================== ПОСТОЯННЫЙ СОСТАВ (7 человек) ====================
        
        $permanentPersonnel = [
            // Командир роты (капитан)
            [
                'first_name' => 'Сергей',
                'last_name' => 'Волков',
                'middle_name' => 'Александрович',
                'position_id' => $commanderPosition->id,
                'rank_id' => $captainRank->id,
                'note' => 'Командир научной роты',
            ],
            // Командиры взводов (лейтенант и старший лейтенант)
            [
                'first_name' => 'Дмитрий',
                'last_name' => 'Морозов',
                'middle_name' => 'Игоревич',
                'position_id' => $platoonCommanderPosition->id,
                'rank_id' => $lieutenantRank->id,
                'note' => 'Командир 1 взвода',
            ],
            [
                'first_name' => 'Андрей',
                'last_name' => 'Кузнецов',
                'middle_name' => 'Петрович',
                'position_id' => $platoonCommanderPosition->id,
                'rank_id' => $seniorLieutenantRank->id,
                'note' => 'Командир 2 взвода',
            ],
            // Старшина роты (прапорщик)
            [
                'first_name' => 'Михаил',
                'last_name' => 'Соколов',
                'middle_name' => 'Николаевич',
                'position_id' => $seniorSergeantPosition->id,
                'rank_id' => $praporshchikRank->id,
                'note' => 'Старшина роты',
            ],
            // Заместители командира взвода
            [
                'first_name' => 'Иван',
                'last_name' => 'Попов',
                'middle_name' => 'Сергеевич',
                'position_id' => $deputyCommanderPosition->id,
                'rank_id' => $sergeantRank->id,
                'note' => 'Заместитель командира 1 взвода',
            ],
            [
                'first_name' => 'Алексей',
                'last_name' => 'Смирнов',
                'middle_name' => 'Владимирович',
                'position_id' => $deputyCommanderPosition->id,
                'rank_id' => $seniorSergeantRank->id,
                'note' => 'Заместитель командира 2 взвода',
            ],
            [
                'first_name' => 'Максим',
                'last_name' => 'Лебедев',
                'middle_name' => 'Алексеевич',
                'position_id' => $deputyCommanderPosition->id,
                'rank_id' => $sergeantRank->id,
                'note' => 'Заместитель командира 3 взвода',
            ],
        ];
        
        // Создаем постоянный состав
        foreach ($permanentPersonnel as $person) {
            Personnel::updateOrCreate(
                [
                    'first_name' => $person['first_name'],
                    'last_name' => $person['last_name'],
                    'middle_name' => $person['middle_name'],
                    'unit_id' => $scientificCompany->id,
                ],
                [
                    'unit_id' => $scientificCompany->id,
                    'position_id' => $person['position_id'],
                    'rank_id' => $person['rank_id'],
                    'current_status_id' => $presentStatus->id,
                    'status_set_by_user_id' => $defaultUserId,
                    'note' => $person['note'],
                ]
            );
        }
        
        $this->command->info('Постоянный состав (7 человек) добавлен');
        
        // ==================== ПЕРЕМЕННЫЙ СОСТАВ (40 человек) ====================
        
        $firstNames = [
            'Александр', 'Алексей', 'Анатолий', 'Андрей', 'Антон', 'Артем', 'Борис', 'Вадим', 
            'Валентин', 'Валерий', 'Василий', 'Виктор', 'Виталий', 'Владимир', 'Вячеслав', 
            'Геннадий', 'Георгий', 'Григорий', 'Даниил', 'Денис', 'Дмитрий', 'Евгений', 
            'Егор', 'Иван', 'Игорь', 'Илья', 'Кирилл', 'Константин', 'Леонид', 'Максим', 
            'Макар', 'Матвей', 'Михаил', 'Никита', 'Николай', 'Олег', 'Павел', 'Петр', 
            'Роман', 'Сергей'
        ];
        
        $lastNames = [
            'Иванов', 'Смирнов', 'Кузнецов', 'Попов', 'Васильев', 'Петров', 'Соколов', 'Михайлов',
            'Новиков', 'Федоров', 'Морозов', 'Волков', 'Алексеев', 'Лебедев', 'Семенов', 'Егоров',
            'Павлов', 'Козлов', 'Степанов', 'Николаев', 'Орлов', 'Андреев', 'Макаров', 'Никитин',
            'Захаров', 'Соловьев', 'Борисов', 'Яковлев', 'Григорьев', 'Романов', 'Воробьев', 'Сергеев',
            'Кузьмин', 'Фролов', 'Александров', 'Дмитриев', 'Королев', 'Гусев', 'Киселев', 'Ильин'
        ];
        
        $middleNames = [
            'Александрович', 'Алексеевич', 'Анатольевич', 'Андреевич', 'Антонович', 'Артемович',
            'Борисович', 'Вадимович', 'Валентинович', 'Валерьевич', 'Васильевич', 'Викторович',
            'Витальевич', 'Владимирович', 'Вячеславович', 'Геннадьевич', 'Георгиевич', 'Григорьевич',
            'Даниилович', 'Денисович', 'Дмитриевич', 'Евгеньевич', 'Егорович', 'Иванович', 'Игоревич',
            'Ильич', 'Кириллович', 'Константинович', 'Леонидович', 'Максимович', 'Макарович',
            'Матвеевич', 'Михайлович', 'Никитич', 'Николаевич', 'Олегович', 'Павлович', 'Петрович',
            'Романович', 'Сергеевич'
        ];
        
        // Создаем 40 операторов
        for ($i = 0; $i < 40; $i++) {
            $firstName = $firstNames[$i % count($firstNames)];
            $lastName = $lastNames[$i % count($lastNames)];
            $middleName = $middleNames[$i % count($middleNames)];
            
            Personnel::updateOrCreate(
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'middle_name' => $middleName,
                    'unit_id' => $scientificCompany->id,
                ],
                [
                    'unit_id' => $scientificCompany->id,
                    'position_id' => $operatorPosition->id,
                    'rank_id' => $cadetRank->id ?? 2,
                    'current_status_id' => $presentStatus->id,
                    'status_set_by_user_id' => $defaultUserId,
                    'note' => 'Оператор научной роты',
                ]
            );
        }
        
        $this->command->info('Переменный состав (40 человек) добавлен');
        
        $totalCount = Personnel::where('unit_id', $scientificCompany->id)->count();
        
        $this->command->info('=====================================');
        $this->command->info('Научная рота успешно заполнена!');
        $this->command->info('Постоянный состав: 7 человек');
        $this->command->info('Переменный состав: 40 человек');
        $this->command->info('Всего: ' . $totalCount . ' человек');
        $this->command->info('=====================================');
    }
}