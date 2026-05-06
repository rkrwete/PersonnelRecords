<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Models\Unit;

class UnitController extends Controller {
    public function index(): JsonResponse {
        $units = Unit::query()
            ->orderBy('name', 'asc')
            ->get();

        $tree = $this->buildTree($units, null);
        $result = [];

        $result = $tree
            ->flatMap(fn ($node) => $node['children'])
            ->sortBy('id')
            ->values();

        return response()->json([
            'data' => $result
        ]);
    }

    private function buildTree($units, $parentId) {
        return $units
            ->where('parent_id', $parentId)
            ->map(function ($unit) use ($units) {
                return [
                    'id' => $unit->id,
                    'name' => $unit->name,
                    'children' => $this->buildTree($units, $unit->id)
                ];
            })
            ->values();
    }
}
