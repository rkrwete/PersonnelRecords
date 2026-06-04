<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar_notes', function (Blueprint $table) {
            $table->time('reminder_time')->nullable()->after('date'); // время напоминания
            $table->enum('reminder_type', ['none', 'once', 'daily', 'weekly', 'monthly'])->default('none')->after('reminder_time');
            $table->boolean('is_notification_sent')->default(false)->after('reminder_type');
        });
    }

    public function down(): void
    {
        Schema::table('calendar_notes', function (Blueprint $table) {
            $table->dropColumn(['reminder_time', 'reminder_type', 'is_notification_sent']);
        });
    }
};