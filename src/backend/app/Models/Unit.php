<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Unit extends Model {
    protected $fillable = ['parent_id', 'name', 'type'];

    public function parent(): BelongsTo {
        return $this->belongsTo(Unit::class, 'parent_id');
    }

    public function children(): HasMany {
        return $this->hasMany(Unit::class, 'parent_id');
    }

    public function personnel(): HasMany {
        return $this->hasMany(Personnel::class);
    }

    public function categories(): HasMany {
        return $this->hasMany(Category::class, 'unit_id');
    }
}
