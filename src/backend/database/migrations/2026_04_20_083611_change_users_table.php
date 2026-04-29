<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            // Удаляем unique-индекс для name, если он есть (для PostgreSQL)
            DB::statement('DROP INDEX IF EXISTS users_name_unique');

            // Удаляем колонку login, если она осталась от предыдущих попыток
            if (Schema::hasColumn('users', 'login')) {
                $table->dropColumn('login');
            }

            // Добавляем login заново с unique
            $table->string('login')->unique()->after('name');
        });
    }

    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['login']);
            $table->dropColumn('login');
            $table->string('name')->unique()->change();
        });
    }
};
