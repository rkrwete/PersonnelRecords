<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar_notes', function (Blueprint $table) {
            $table->boolean('is_recurring')->default(false)->after('content');
            $table->string('color', 20)->default('#2196F3')->after('is_recurring');
        });
    }

    public function down(): void
    {
        Schema::table('calendar_notes', function (Blueprint $table) {
            $table->dropColumn(['is_recurring', 'color']);
        });
    }
};
