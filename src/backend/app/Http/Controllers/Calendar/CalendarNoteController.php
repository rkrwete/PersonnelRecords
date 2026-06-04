<?php

namespace App\Http\Controllers\Calendar;

use App\Models\CalendarNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class CalendarNoteController extends Controller
{
    public function index()
    {
        $notes = CalendarNote::where('user_id', Auth::id())
            ->orderBy('date', 'desc')
            ->orderBy('reminder_time', 'asc')
            ->get();

        return response()->json($notes);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'required|string|max:5000',
            'date' => 'required|string',
            'reminder_time' => 'nullable|string',
            'reminder_type' => 'nullable|in:none,once,daily,weekly,monthly',
            'is_recurring' => 'boolean',
            'color' => 'nullable|string|max:20',
        ]);
        
        // Преобразуем дату в формат YYYY-MM-DD
        $date = date('Y-m-d', strtotime($request->date));
        
        // Сохраняем только время HH:MM:SS
        $reminderTime = null;
        if ($request->reminder_time && $request->reminder_type !== 'none') {
            $reminderTime = date('H:i:s', strtotime($request->reminder_time));
        }
        
        $note = CalendarNote::create([
            'user_id' => Auth::id(),
            'title' => $request->title,
            'content' => $request->content,
            'date' => $date,
            'reminder_time' => $reminderTime,
            'reminder_type' => $request->reminder_type ?? 'none',
            'is_recurring' => $request->is_recurring ?? false,
            'color' => $request->color ?? '#2196F3',
            'is_notification_sent' => false,
        ]);
        
        return response()->json($note, 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'required|string|max:5000',
            'reminder_time' => 'nullable|date_format:H:i',
            'reminder_type' => 'nullable|in:none,once,daily,weekly,monthly',
            'is_recurring' => 'boolean',
            'color' => 'nullable|string|max:20',
        ]);

        $note = CalendarNote::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $note->update([
            'title' => $request->title,
            'content' => $request->content,
            'reminder_time' => $request->reminder_time,
            'reminder_type' => $request->reminder_type ?? $note->reminder_type,
            'is_recurring' => $request->is_recurring ?? $note->is_recurring,
            'color' => $request->color ?? $note->color,
            'is_notification_sent' => false, // Сбрасываем флаг при обновлении
        ]);

        return response()->json($note);
    }

    public function destroy($id)
    {
        $note = CalendarNote::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $note->delete();

        return response()->json(['message' => 'Заметка удалена']);
    }

    public function upcomingReminders()
    {
        $now = now();
        $currentTime = $now->format('H:i');
        $today = $now->format('Y-m-d');
        
        $notes = CalendarNote::where('reminder_type', 'once')
            ->where('is_notification_sent', false)
            ->get();
        
        $debug = [];
        $reminders = [];
        
        foreach ($notes as $note) {
            $noteTime = date('H:i', strtotime($note->reminder_time));
            $noteDate = date('Y-m-d', strtotime($note->date));
            
            $match = ($noteTime === $currentTime && $noteDate === $today);
            
            $debug[] = [
                'id' => $note->id,
                'note_time' => $noteTime,
                'current_time' => $currentTime,
                'time_match' => ($noteTime === $currentTime),
                'note_date' => $noteDate,
                'current_date' => $today,
                'date_match' => ($noteDate === $today),
                'both_match' => $match
            ];
            
            if ($match) {
                $reminders[] = $note;
            }
        }
        
        return response()->json([
            'current_server_time' => $now->format('H:i:s'),
            'current_date' => $today,
            'current_time' => $currentTime,
            'debug' => $debug,
            'reminders' => $reminders
        ]);
    }
}