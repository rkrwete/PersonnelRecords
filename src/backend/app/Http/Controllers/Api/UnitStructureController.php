<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Models\Category;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UnitStructureController extends Controller {
    public function saveStructure(Request $request, $unitId) {
        if (!is_numeric($unitId)) {
            return response()->json(['error' => 'Некорректный идентификатор подразделения'], 400);
        }

        if (!$request->has('categories') || !is_array($request->categories)) {
            return response()->json(['error' => 'Данные категорий отсутствуют или имеют неверный формат'], 422);
        }

        try {
            return DB::transaction(function () use ($request, $unitId) {
                $unit = Unit::find($unitId);
                if (!$unit) {
                    return response()->json(['error' => 'Подразделение не найдено'], 404);
                }

                $incomingCategoryIds = collect($request->categories)
                    ->pluck('id')
                    ->filter(fn($id) => is_numeric($id))
                    ->toArray();

                $unit->categories()->whereNotIn('id', $incomingCategoryIds)->delete();

                foreach ($request->categories as $catData) {
                    $category = Category::updateOrCreate(
                        ['id' => is_numeric($catData['id']) ? $catData['id'] : null],
                        ['unit_id' => $unitId, 'name' => $catData['name']]
                    );

                    $incomingPositionIds = collect($catData['positions'] ?? [])
                        ->pluck('id')
                        ->filter(fn($id) => is_numeric($id))
                        ->toArray();

                    $category->positions()->whereNotIn('id', $incomingPositionIds)->delete();

                    foreach ($catData['positions'] ?? [] as $posData) {
                        Position::updateOrCreate(
                            ['id' => is_numeric($posData['id'] ?? null) ? $posData['id'] : null],
                            [
                                'category_id' => $category->id,
                                'title' => $posData['title'],
                                'count' => $posData['count']
                            ]
                        );
                    }
                }

                $updatedStructure = $unit->categories()->with('positions')->get();
                return response()->json($updatedStructure, 200);
            });

        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'error' => 'Ошибка базы данных при сохранении',
                'details' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Произошла непредвиденная ошибка',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getStructure($unitId) {
        $unit = Unit::findOrFail($unitId);
        return $unit->categories()->with('positions')->get();
    }
}
