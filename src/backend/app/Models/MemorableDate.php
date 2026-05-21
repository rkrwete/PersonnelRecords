<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemorableDate extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'date', 'description', 'is_recurring', 'color'];

    protected $casts = [
        'is_recurring' => 'boolean',
    ];
}