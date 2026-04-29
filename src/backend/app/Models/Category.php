<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Category extends Model
{
    // Явно указываем имя таблицы
    protected $table = 'state_categories';

    protected $fillable = ['unit_id', 'name'];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function positions(): HasMany
    {
        // Убедитесь, что в таблице positions внешний ключ называется category_id
        return $this->hasMany(Position::class, 'category_id');
    }
}
