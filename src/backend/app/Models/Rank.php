<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rank extends Model {
    protected $fillable = ['name', 'level'];

    public function personnel(): HasMany {
        return $this->hasMany(Personnel::class);
    }
}
