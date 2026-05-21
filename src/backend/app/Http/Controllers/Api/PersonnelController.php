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

    public function groupedByCategory($unitId): JsonResponse {
        if (!is_numeric($unitId)) {
            return response()->json(['error' => 'Invalid ID'], 400);
        }

        $allUnitIds = $this->getAllChildUnitIds($unitId);
        $unitsData = DB::table('units')->whereIn('id', $allUnitIds)->get()->keyBy('id');
        $allCategories = DB::table('state_categories')->get()->keyBy('id');

        $personnelRows = DB::table('personnel')
            ->select('personnel.*', 'positions.title as position_title', 'positions.category_id', 'positions.count as pos_count')
            ->join('positions', 'positions.id', '=', 'personnel.position_id')
            ->whereIn('personnel.unit_id', $allUnitIds)
            ->orderBy('personnel.rank_id', 'desc')
            ->get();

        $groupedData = [];
        $usedPositions = [];

        foreach ($personnelRows as $row) {
            $uId = $row->unit_id;
            $cId = $row->category_id;

            $groupedData[$uId][$cId]['personnel'][] = [
                'id' => $row->id,
                'last_name' => $row->last_name,
                'first_name' => $row->first_name,
                'middle_name' => $row->middle_name,
                'rank_id' => $row->rank_id,
                'current_status_id' => $row->current_status_id,
                'note' => $row->note,
                'position' => ['id' => $row->position_id, 'title' => $row->position_title],
            ];

            $posKey = "{$uId}_{$cId}_{$row->position_id}";
            if (!isset($usedPositions[$posKey])) {
                $groupedData[$uId][$cId]['shtat'] = ($groupedData[$uId][$cId]['shtat'] ?? 0) + ($row->pos_count ?? 0);
                $usedPositions[$posKey] = true;
            }
        }

        $buildTree = function ($currentId) use (&$buildTree, $unitsData, $groupedData, $allCategories) {
            if (!isset($unitsData[$currentId])) return null;

            $unit = $unitsData[$currentId];
            $childUnits = [];

            $directChildren = $unitsData->where('parent_id', $currentId);
            foreach ($directChildren as $child) {
                $childNode = $buildTree($child->id);
                if ($childNode !== null) {
                    $childUnits[] = $childNode;
                }
            }

            usort($childUnits, function ($a, $b) {
                $aIsTarget = mb_strtolower($a['name']) === 'научная рота';
                $bIsTarget = mb_strtolower($b['name']) === 'научная рота';

                if ($aIsTarget && !$bIsTarget) return -1; // $a выше
                if (!$aIsTarget && $bIsTarget) return 1;  // $b выше

                // Если ни одно, либо оба — "Научная рота" (маловероятно), сортируем по ID
                return $a['id'] <=> $b['id'];
            });

            $categories = [];
            $totalShtat = 0;

            if (isset($groupedData[$currentId])) {
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
            'current_status_id' => $request->current_status_id,
            'note' => $request->input('note', '') ?? ''
        ]);

        return response()->json(['success' => true]);
    }
}
