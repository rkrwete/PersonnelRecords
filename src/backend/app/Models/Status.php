<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Status extends Model {
    public $timestamps = false;
    protected $fillable = ['name'];

    public function personnel(): HasMany {
        return $this->hasMany(Personnel::class, 'current_status_id');
    }
}
