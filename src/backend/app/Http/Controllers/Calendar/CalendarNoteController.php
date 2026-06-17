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
        
        $currentYear = now()->year;
        
        $notes = $notes->map(function($note) use ($currentYear) {
            // Если это повторяющаяся заметка (is_recurring = true)
            if ($note->is_recurring) {
                // Извлекаем месяц и день из даты
                $dateParts = explode('-', $note->date);
                if (count($dateParts) === 3) {
                    // Заменяем год на текущий, оставляем месяц и день
                    $note->date = $currentYear . '-' . $dateParts[1] . '-' . $dateParts[2];
                }
            }
            return $note;
        });
        
        return response()->json($notes);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'required|string|max:5000',
            'date' => 'required|string',
            'is_recurring' => 'boolean',
            'color' => 'nullable|string|max:20',
            'reminder_time' => 'nullable|string',
        ]);
        
        $date = $request->date;
        $reminderType = $request->reminder_type ?? 'none';
        
        // Если это повторяющаяся заметка, устанавливаем reminder_type = 'monthly'
        if ($request->is_recurring) {
            $reminderType = 'monthly';
            // Если дата пришла без года, добавляем текущий
            if (strlen($date) === 5 && strpos($date, '-') !== false) {
                $date = now()->year . '-' . $date;
            }
        }
        
        $note = CalendarNote::create([
            'user_id' => Auth::id(),
            'title' => $request->title,
            'content' => $request->content,
            'date' => $date,
            'is_recurring' => $request->is_recurring ?? false,
            'color' => $request->color ?? '#2196F3',
            'reminder_time' => $request->reminder_time,
            'reminder_type' => $reminderType,
            'is_notification_sent' => false,
        ]);
        
        return response()->json($note, 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'required|string|max:5000',
            'date' => 'required|string',
            'is_recurring' => 'boolean',
            'color' => 'nullable|string|max:20',
            'reminder_time' => 'nullable|string',
        ]);

        $note = CalendarNote::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $date = $request->date;
        $reminderType = $request->reminder_type ?? 'none';
        
        if ($request->is_recurring) {
            $reminderType = 'monthly';
            if (strlen($date) === 5 && strpos($date, '-') !== false) {
                $date = now()->year . '-' . $date;
            }
        }

        $note->update([
            'title' => $request->title,
            'content' => $request->content,
            'date' => $date,
            'is_recurring' => $request->is_recurring ?? false,
            'color' => $request->color ?? $note->color,
            'reminder_time' => $request->reminder_time,
            'reminder_type' => $reminderType,
            'is_notification_sent' => false,
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
        
        $notes = CalendarNote::where('reminder_type', '!=', 'none')
            ->where('is_notification_sent', false)
            ->get();
        
        $reminders = [];
        
        foreach ($notes as $note) {
            $noteTime = date('H:i', strtotime($note->reminder_time));
            $shouldNotify = false;
            
            if ($note->is_recurring) {
                // Для ежегодных: проверяем месяц и день (год игнорируем)
                $noteMonth = date('m', strtotime($note->date));
                $noteDay = date('d', strtotime($note->date));
                $todayMonth = $now->format('m');
                $todayDay = $now->format('d');
                
                if ($noteMonth === $todayMonth && $noteDay === $todayDay && $noteTime === $currentTime) {
                    $shouldNotify = true;
                }
            } elseif ($note->reminder_type === 'once') {
                // Одноразовая: проверяем полную дату
                $noteDate = date('Y-m-d', strtotime($note->date));
                if ($noteDate === $today && $noteTime === $currentTime) {
                    $shouldNotify = true;
                }
            } elseif ($note->reminder_type === 'daily') {
                if ($noteTime === $currentTime) {
                    $shouldNotify = true;
                }
            }
            
            if ($shouldNotify) {
                $reminders[] = $note;
            }
        }
        
        return response()->json($reminders);
    }
}