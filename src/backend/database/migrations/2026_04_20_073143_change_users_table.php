<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'email')) {
                $table->dropUnique('users_email_unique');
                $table->dropColumn('email');
            }
            $table->string('name')->unique()->change();
            $table->foreignId('role_id')->constrained();
            $table->foreignId('unit_id')->nullable()->constrained();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
