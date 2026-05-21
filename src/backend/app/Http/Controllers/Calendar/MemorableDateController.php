<?php

namespace App\Http\Controllers\Calendar;

use App\Models\MemorableDate;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class MemorableDateController extends Controller
{
    public function index()
    {
        $dates = MemorableDate::orderBy('date')->get();
        return response()->json($dates);
    }

    public function show($id)
    {
        $date = MemorableDate::findOrFail($id);
        return response()->json($date);
    }
}