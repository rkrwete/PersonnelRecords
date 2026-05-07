<?php

namespace App\Services\DutyRoster;

use App\Models\Status;
use App\Models\Unit;
use App\Models\Position;
use Illuminate\Support\Collection;

class DutyRosterService
{
    private Collection $statuses;
    private int $presentStatusId;

    public function __construct()
    {
        $this->statuses = Status::all();
        $presentStatus = $this->statuses->where('name', 'Налицо')->first();
        $this->presentStatusId = $presentStatus ? $presentStatus->id : 1;
    }

    public function getStatuses(): Collection
    {
        return $this->statuses;
    }

    public function getAbsentStatuses(): Collection
    {
        return $this->statuses->where('id', '!=', $this->presentStatusId);
    }

    public function getPresentStatusId(): int
    {
        return $this->presentStatusId;
    }

    public function countByStatus(Collection $personnel, int $statusId): int
    {
        return $personnel->where('current_status_id', $statusId)->count();
    }

    public function getStatusStats(Collection $personnel): array
    {
        $stats = [];
        foreach ($this->statuses as $status) {
            $stats[$status->name] = $this->countByStatus($personnel, $status->id);
        }
        return $stats;
    }

    public function getStaffCount(int $unitId, ?array $positionIds = null): int
    {
        $staffCount = 0;
        
        $query = Position::whereHas('category', function ($q) use ($unitId) {
            $q->where('unit_id', $unitId);
        });
        
        if ($positionIds !== null && !empty($positionIds)) {
            $query->whereIn('id', $positionIds);
        }
        
        foreach ($query->get() as $position) {
            $staffCount += $position->count;
        }
        
        return $staffCount;
    }

    public function getStaffCountByRankCategory(int $unitId, array $categoryRanks): int
    {
        $staffCount = 0;
        
        $positions = Position::whereHas('category', function ($query) use ($unitId) {
            $query->where('unit_id', $unitId);
        })->with('personnel.rank')->get();
        
        foreach ($positions as $position) {
            foreach ($position->personnel as $person) {
                if ($person->rank && in_array($person->rank->id, $categoryRanks)) {
                    $staffCount += $position->count;
                    break; // Добавляем только один раз на должность
                }
            }
        }
        
        return $staffCount;
    }

    public function getBasicStats(Collection $personnel): array
    {
        return [
            'total' => $personnel->count(),
            'present' => $personnel->where('current_status_id', $this->presentStatusId)->count(),
        ];
    }

    public function getFullStats(Collection $personnel): array
    {
        $stats = $this->getBasicStats($personnel);
        
        foreach ($this->getAbsentStatuses() as $status) {
            $stats[$status->name] = $this->countByStatus($personnel, $status->id);
        }
        
        return $stats;
    }

    public function getAbsentPersonnel(Collection $personnel, string $categoryName = ''): array
    {
        $absentList = [];
        
        foreach ($personnel as $p) {
            if ($p->current_status_id != $this->presentStatusId) {
                $absentList[] = [
                    'rank' => $p->rank->name ?? '',
                    'fio' => trim($p->last_name . ' ' . $p->first_name . ' ' . $p->middle_name),
                    'category' => $categoryName ?: ($p->category->name ?? ''),
                    'reason' => $p->currentStatus->name ?? 'Неизвестно'
                ];
            }
        }
        
        return $absentList;
    }
}