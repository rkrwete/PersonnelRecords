<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable {
    use HasApiTokens;
    protected $fillable = ['name', 'login', 'email', 'password', 'role_id', 'unit_id'];

    public function role(): BelongsTo {
        return $this->belongsTo(Role::class);
    }

    public function managedUnit(): BelongsTo {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function hasRole(string $slug): bool {
        return $this->role->slug === $slug;
    }
}
