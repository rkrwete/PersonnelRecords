<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    public function store(Request $request): JsonResponse {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|integer|exists:units,id',
        ]);

        $validated['type'] = 'подразделение';

        $unit = Unit::create($validated);
        return response()->json($unit, 201);
    }

    public function update(Request $request, $id): JsonResponse {
        $unit = Unit::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|integer|exists:units,id|not_in:'.$id,
        ]);

        $unit->update($validated);
        return response()->json($unit);
    }

    public function destroy($id): JsonResponse {
        $unit = Unit::findOrFail($id);

        if ($unit->children()->count() > 0) {
            return response()->json(['error' => 'Невозможно удалить подразделение, имеющее дочерние элементы'], 422);
        }

        $unit->delete();
        return response()->json(['message' => 'Подразделение успешно удалено']);
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
