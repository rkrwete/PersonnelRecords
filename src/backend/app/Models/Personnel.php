<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Personnel extends Model {
    protected $table = 'personnel';

    protected $fillable = [
        'id',
        'unit_id',
        'rank_id',
        'current_status_id',
        'status_set_by_user_id',
        'note',
        'first_name',
        'last_name',
        'middle_name',
        'position_id',
        'photo_path'
    ];

    public function unit(): BelongsTo {
        return $this->belongsTo(Unit::class);
    }
    public function rank(): BelongsTo {
        return $this->belongsTo(Rank::class);
    }

    public function position() {
        return $this->belongsTo(Position::class, 'position_id');
    }
    public function currentStatus(): BelongsTo {
        return $this->belongsTo(Status::class, 'current_status_id');
    }

    public function statusSetter(): BelongsTo {
        return $this->belongsTo(User::class, 'status_set_by_user_id');
    }

    public function canChangeStatus(User $user): bool {
        if ($user->role->slug === 'drill') return true;
        $currentPriority = $this->statusSetter->role->priority;
        $userPriority = $user->role->priority;
        return $userPriority >= $currentPriority;
    }
}
