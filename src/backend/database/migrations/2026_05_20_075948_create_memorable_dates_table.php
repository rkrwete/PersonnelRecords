<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('memorable_dates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('date'); // формат: MM-DD (например, 02-23)
            $table->text('description')->nullable();
            $table->boolean('is_recurring')->default(true);
            $table->string('color', 20)->default('#FF9800');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('memorable_dates');
    }
};