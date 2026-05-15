<?php

use App\Http\Controllers\Api\UnitController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UnitStructureController;
use App\Http\Controllers\Api\PersonnelController;
use App\Http\Controllers\Api\UserController;

//контроллеры для строевых записок
use App\Http\Controllers\DutyRoster\DutyRosterExportController;
use App\Http\Controllers\DutyRoster\AcademicDutyRosterController;



Route::post('/login', [AuthController::class, 'login']);
Route::get('/test', fn () => ['ok' => true]);
Route::get('/units', [UnitController::class, 'index']);
Route::get('units/{unitId}/personnel/grouped', [PersonnelController::class, 'groupedByCategory']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user/profile', [AuthController::class, 'me']);

    Route::prefix('units/{unitId}')->group(function () {
        Route::get('/structure', [UnitStructureController::class, 'getStructure']);
        Route::post('/structure', [UnitStructureController::class, 'saveStructure']);
        Route::post('/personnel', [PersonnelController::class, 'store']);
        Route::get('/personnel', [PersonnelController::class, 'index']);
    });

    Route::patch('/personnel/{id}', [PersonnelController::class, 'changeStatus']);

    Route::post('/units', [UnitController::class, 'store']);
    Route::patch('/units/{id}', [UnitController::class, 'update']);
    Route::delete('/units/{id}', [UnitController::class, 'destroy']);

    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::patch('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
});

// получение строевой записки на подразделение
Route::get('/duty-roster/export/{unitId}', [DutyRosterExportController::class, 'export']);
// получение строевой записки на академию
Route::get('/academic-duty-roster/export/{unitId?}', [AcademicDutyRosterController::class, 'export']);
