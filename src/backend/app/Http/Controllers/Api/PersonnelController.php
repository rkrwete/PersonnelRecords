<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PersonnelResource;
use App\Models\Personnel;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PersonnelController extends Controller {
    public function index($unitId) {
        if (!is_numeric($unitId)) {
            return response()->json(['error' => 'Invalid ID'], 400);
        }

        $personnel = Personnel::where('unit_id', $unitId)
            ->orderBy('id', 'asc')
            ->get();

        return PersonnelResource::collection($personnel);
    }

    public function store(Request $request, $unitId) {
        if (!is_numeric($unitId)) {
            return response()->json(['error' => 'Invalid ID'], 400);
        }

        return DB::transaction(function () use ($request, $unitId) {
            $unit = Unit::findOrFail($unitId);

            $incomingIds = collect($request->all())
                ->pluck('id')
                ->filter(fn($id) => is_numeric($id))
                ->toArray();

            $unit->personnel()->whereNotIn('id', $incomingIds)->delete();

            foreach ($request->all() as $pData) {
                $id = $pData['id'] ?? null;

                if (is_numeric($id)) {
                    Personnel::where('id', $id)->update([
                        'unit_id'     => $unitId,
                        'position_id' => $pData['positionId'],
                        'rank_id'     => $pData['rankId'],
                        'last_name'   => $pData['lastName'],
                        'first_name'  => $pData['firstName'],
                        'middle_name' => $pData['middleName'],
                    ]);
                } else {
                    Personnel::create([
                        'unit_id'     => $unitId,
                        'position_id' => $pData['positionId'],
                        'rank_id'     => $pData['rankId'],
                        'last_name'   => $pData['lastName'],
                        'first_name'  => $pData['firstName'],
                        'middle_name' => $pData['middleName'],
                        'current_status_id' => 1,
                        'status_set_by_user_id' => $request->user()->id,
                        'note' => '',
                    ]);
                }
            }

            $result = $unit->personnel()->get();

            return PersonnelResource::collection($result);
        });
    }
/*
    public function groupedByCategory($unitId) {
        if (!is_numeric($unitId)) {
            return response()->json(['error' => 'Invalid ID'], 400);
        }

        $stateCounts = DB::table('positions')
            ->select(
                'category_id',
                DB::raw('SUM(count) as shtat')
            )
            ->groupBy('category_id');

        $rows = DB::table('personnel')
            ->select(
                'personnel.id',
                'personnel.last_name',
                'personnel.first_name',
                'personnel.middle_name',
                'personnel.rank_id',
                'personnel.current_status_id',
                'positions.id as position_id',
                'positions.title as position_title',
                'state_categories.id as category_id',
                'state_categories.name as category_name',
                'sc.shtat as shtat'
            )
            ->leftJoin('positions', 'positions.id', '=', 'personnel.position_id')
            ->leftJoin('state_categories', 'state_categories.id', '=', 'positions.category_id')
            ->leftJoinSub($stateCounts, 'sc', function ($join) {
                $join->on('sc.category_id', '=', 'state_categories.id');
            })
            ->where('personnel.unit_id', $unitId)

            ->orderBy('state_categories.id')
            ->orderByDesc('personnel.rank_id')
            ->orderBy('personnel.last_name')
            ->get();

        $grouped = [];

        foreach ($rows as $row) {
            $catId = $row->category_id;

            if (!isset($grouped[$catId])) {
                $grouped[$catId] = [
                    'id' => $catId,
                    'name' => $row->category_name,
                    'shtat' => (int) ($row->shtat ?? 0),
                    'personnel' => [],
                ];
            }

            $grouped[$catId]['personnel'][] = [
                'id' => $row->id,
                'last_name' => $row->last_name,
                'first_name' => $row->first_name,
                'middle_name' => $row->middle_name,
                'rank_id' => $row->rank_id,
                'current_status_id' => $row->current_status_id,
                'position' => [
                    'id' => $row->position_id,
                    'title' => $row->position_title,
                ],
            ];
        }

        return response()->json($grouped);
    }*/

    public function groupedByCategory($unitId): JsonResponse {
        if (!is_numeric($unitId)) {
            return response()->json(['error' => 'Invalid ID'], 400);
        }

        $allUnitIds = $this->getAllChildUnitIds($unitId);
        $unitsData = DB::table('units')->whereIn('id', $allUnitIds)->get()->keyBy('id');
        $allCategories = DB::table('state_categories')->get()->keyBy('id');

        // 1. Получаем ПЕРСОНАЛ и сразу группируем (Unit -> Category)
        $personnelRows = DB::table('personnel')
            ->select('personnel.*', 'positions.title as position_title', 'positions.category_id', 'positions.count as pos_count')
            ->join('positions', 'positions.id', '=', 'personnel.position_id')
            ->whereIn('personnel.unit_id', $allUnitIds)
            ->orderBy('personnel.rank_id', 'desc')
            ->get();

        $groupedData = []; // Структура: [unit_id][category_id] = ['personnel' => [], 'shtat_sum' => 0]
        $usedPositions = []; // Чтобы не считать одну и ту же позицию в штатку дважды для одного юнита

        foreach ($personnelRows as $row) {
            $uId = $row->unit_id;
            $cId = $row->category_id;

            // Добавляем человека
            $groupedData[$uId][$cId]['personnel'][] = [
                'id' => $row->id,
                'last_name' => $row->last_name,
                'first_name' => $row->first_name,
                'middle_name' => $row->middle_name,
                'rank_id' => $row->rank_id,
                'current_status_id' => $row->current_status_id,
                'position' => ['id' => $row->position_id, 'title' => $row->position_title],
            ];

            // Считаем штатку (только один раз для каждой уникальной позиции в рамках юнита)
            $posKey = "{$uId}_{$cId}_{$row->position_id}";
            if (!isset($usedPositions[$posKey])) {
                $groupedData[$uId][$cId]['shtat'] = ($groupedData[$uId][$cId]['shtat'] ?? 0) + ($row->pos_count ?? 0);
                $usedPositions[$posKey] = true;
            }
        }

        // 2. Рекурсивная сборка дерева
        $buildTree = function ($currentId) use (&$buildTree, $unitsData, $groupedData, $allCategories) {
            $unit = $unitsData[$currentId];

            // Рекурсивно собираем детей
            $childUnits = [];
            $directChildren = $unitsData->where('parent_id', $currentId);
            foreach ($directChildren as $child) {
                $childNode = $buildTree($child->id);
                if ($childNode !== null) {
                    $childUnits[] = $childNode;
                }
            }

            // Если в юните нет людей и нет активных детей — скрываем ветку
            $hasDataInUnit = isset($groupedData[$currentId]);
            if (!$hasDataInUnit && empty($childUnits)) {
                return null;
            }

            // Формируем категории для этого юнита
            $categories = [];
            $totalShtat = 0;
            if ($hasDataInUnit) {
                foreach ($groupedData[$currentId] as $catId => $data) {
                    $categories[] = [
                        'id' => $catId,
                        'name' => $allCategories[$catId]->name ?? 'Без категории',
                        'shtat' => (int)($data['shtat'] ?? 0),
                        'personnel' => $data['personnel']
                    ];
                    $totalShtat += ($data['shtat'] ?? 0);
                }
            }

            return [
                'id' => $unit->id,
                'name' => $unit->name,
                'shtat' => $totalShtat,
                'categories' => $categories,
                'units' => $childUnits
            ];
        };

        $result = $buildTree($unitId);

        if (!$result) {
            $root = DB::table('units')->where('id', $unitId)->first();
            return response()->json([
                'id' => $root->id,
                'name' => $root->name,
                'shtat' => 0,
                'units' => []
            ]);
        }

        return response()->json($result);
    }

    private function getAllChildUnitIds($unitId) {
        $ids = [$unitId];

        $children = DB::table('units')
            ->where('parent_id', $unitId)
            ->pluck('id');

        foreach ($children as $childId) {
            $ids = array_merge($ids, $this->getAllChildUnitIds($childId));
        }

        return $ids;
    }

    public function changeStatus(Request $request, $id) {
        $person = Personnel::findOrFail($id);

        $person->update([
            'current_status_id' => $request->current_status_id
        ]);

        return response()->json(['success' => true]);
    }
}
