<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonnelResource extends JsonResource {
    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'unitId' => $this->unit_id,
            'positionId' => $this->position_id,
            'rankId' => $this->rank_id,
            'lastName' => $this->last_name,
            'firstName' => $this->first_name,
            'middleName' => $this->middle_name,
            'currentStatusId' => $this->current_status_id,
            'statusSetByUserId' => $this->status_set_by_user_id,
            'photo' => $this->photo_path ? asset('storage/' . $this->photo_path) : null,
            'note' => $this->note,
        ];
    }
}
