<?php

namespace App\Http\Controllers\Calendar;

use App\Models\CalendarNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
/*
class CalendarNoteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // Получить все заметки текущего пользователя
    public function index()
    {
        $notes = CalendarNote::where('user_id', Auth::id())
            ->orderBy('date', 'desc')
            ->get();
        
        return response()->json($notes);
    }

    // Получить заметку за конкретную дату
    public function show($date)
    {
        try {
            \Log::info('show calendar note', ['date' => $date, 'user_id' => Auth::id()]);
            $userId = 6; // временно для теста, потом замените на Auth::id()
            $note = CalendarNote::where('user_id', $userId)
                ->where('date', $date)
                ->first();
            return response()->json($note);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'content' => 'required|string|max:5000'
        ]);
        
        $note = CalendarNote::create([
            'user_id' => Auth::id(),
            'date' => $request->date,
            'content' => $request->content
        ]);
        
        return response()->json($note, 201);
    }
    
    // Обновить существующую заметку по id
    public function update(Request $request, $id)
    {
        $request->validate([
            'content' => 'required|string|max:5000'
        ]);

        $note = CalendarNote::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $note->update(['content' => $request->content]);

        return response()->json($note);
    }

    // Удалить заметку
    public function destroy($id)
    {
        $note = CalendarNote::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $note->delete();

        return response()->json(['message' => 'Заметка удалена']);
    }
}*/



class CalendarNoteController extends Controller
{
   

    // Получить все заметки текущего пользователя
    public function index()
    {
        $notes = CalendarNote::where('user_id', Auth::id())
            ->orderBy('date', 'desc')
            ->get();
        
        return response()->json($notes);
    }

    // Получить заметку за конкретную дату
    public function show($date)
    {
        $note = CalendarNote::where('user_id', Auth::id())
            ->where('date', $date)
            ->first();
        
        return response()->json($note);
    }

    // Создать заметку
    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'content' => 'required|string|max:5000'
        ]);
        
        $note = CalendarNote::create([
            'user_id' => Auth::id(),
            'date' => $request->date,
            'content' => $request->content
        ]);
        
        return response()->json($note, 201);
    }
    
    // Обновить существующую заметку по id
    public function update(Request $request, $id)
    {
        $request->validate([
            'content' => 'required|string|max:5000'
        ]);

        $note = CalendarNote::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $note->update(['content' => $request->content]);

        return response()->json($note);
    }

    // Удалить заметку
    public function destroy($id)
    {
        $note = CalendarNote::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $note->delete();

        return response()->json(['message' => 'Заметка удалена']);
    }
}