<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
     DB::table('personnel')
        ->whereNotIn('current_status_id', [3, 4, 5, 6])
        ->update([
            'current_status_id' => 1
        ]);
})->dailyAt('6:00');