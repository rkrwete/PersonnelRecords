<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $academy = Unit::firstOrCreate([
            'name' => 'Военная академия связи',
            'type' => 'академия',
            'parent_id' => null
        ]);

        Unit::firstOrCreate([
            'name' => 'Командование',
            'type' => 'подразделение',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => 'Мед. служба',
            'type' => 'подразделение',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => 'ГрОМР',
            'type' => 'подразделение',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => 'Отдел кадров',
            'type' => 'подразделение',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => 'Отдел материального обеспечения',
            'type' => 'подразделение',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => 'Отдел технического обеспечения',
            'type' => 'подразделение',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => 'Отделение по ВПР',
            'type' => 'подразделение',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => 'Служба ЗГТ',
            'type' => 'подразделение',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => 'Строевой отдел',
            'type' => 'подразделение',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => 'УМО',
            'type' => 'подразделение',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => '1 факультет',
            'type' => 'факультет',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => '11 кафедра',
            'type' => 'кафедра',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => '12 кафедра',
            'type' => 'кафедра',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => '2 факультет',
            'type' => 'факультет',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => '21 кафедра',
            'type' => 'кафедра',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => '22 кафедра',
            'type' => 'кафедра',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => '3 факультет',
            'type' => 'факультет',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => '31 кафедра',
            'type' => 'кафедра',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => '32 кафедра',
            'type' => 'кафедра',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => '4 факультет',
            'type' => 'факультет',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => '41 кафедра',
            'type' => 'кафедра',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => '42 кафедра',
            'type' => 'кафедра',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => '44 кафедра',
            'type' => 'кафедра',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => '45 кафедра',
            'type' => 'кафедра',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => '5 факультет',
            'type' => 'факультет',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => 'ФППК и ЗО',
            'type' => 'подразделение',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => '6 факультет',
            'type' => 'факультет',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => 'Отделение СПО',
            'type' => 'подразделение',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => '1 кафедра',
            'type' => 'кафедра',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => '2 кафедра',
            'type' => 'кафедра',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => '4 кафедра',
            'type' => 'кафедра',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => '5 кафедра',
            'type' => 'кафедра',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => '6 кафедра',
            'type' => 'кафедра',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => '10 кафедра',
            'type' => 'кафедра',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => '20 кафедра',
            'type' => 'кафедра',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => 'НИЦ',
            'type' => 'подразделение',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => 'НИЛ(ПОС)',
            'type' => 'подразделение',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => 'НИЛ(ООБД)',
            'type' => 'подразделение',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => 'УЦ(ИТ)',
            'type' => 'подразделение',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => 'Курсы младших офицеров',
            'type' => 'подразделение',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => 'Научная рота',
            'type' => 'подразделение',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => 'Узел связи',
            'type' => 'подразделение',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => 'ЦОИ',
            'type' => 'подразделение',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => 'Военный оркестр',
            'type' => 'подразделение',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => 'Склады',
            'type' => 'подразделение',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => 'Б(ОУП)',
            'type' => 'подразделение',
            'parent_id' => $academy->id
        ]);

        Unit::firstOrCreate([
            'name' => 'Отделение охраны ФСБ',
            'type' => 'подразделение',
            'parent_id' => $academy->id
        ]);
    }
}
