<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model {
    protected $fillable = ['category_id', 'title', 'count'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function personnel(): HasMany
    {
        return $this->hasMany(Personnel::class);
    }

    public function getVacanciesCountAttribute(): int
    {
        return $this->count - $this->personnel()->count();
    }
}
