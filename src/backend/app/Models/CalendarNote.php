<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'title', 'content', 'date', 
        'reminder_time', 'reminder_type', 'is_notification_sent',
        'is_recurring', 'color'
    ];

    protected $casts = [
        'is_recurring' => 'boolean',
        'is_notification_sent' => 'boolean',
        'date' => 'date',
        'reminder_time' => 'datetime',
    ];
}