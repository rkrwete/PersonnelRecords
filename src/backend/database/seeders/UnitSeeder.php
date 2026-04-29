<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $academy = Unit::create([
            'name' => 'Военная академия',
            'type' => 'академия',
            'parent_id' => null
        ]);

        Unit::create([
            'name' => '31 кафедра',
            'type' => 'кафедра',
            'parent_id' => $academy->id
        ]);

        Unit::create([
            'name' => 'Научная рота',
            'type' => 'подразделение',
            'parent_id' => $academy->id
        ]);
    }
}
