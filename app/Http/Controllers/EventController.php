<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    public function index(Request $request)
    {
        return Event::with('creator')
            ->orderBy('start_datetime', 'desc')
            ->get();
    }

    public function publicIndex(Request $request)
    {
        $college = $request->input('college', 'law');
        $grade = $request->input('grade', 1);
        $term = $request->input('term', 'First');
        $planType = $request->input('plan_type', 'free');

        // Validate plan type
        if (!in_array($planType, ['free', 'basic', 'pro'])) {
            return response()->json(['message' => 'Invalid plan type'], 422);
        }

        return Event::with('creator')
            ->where('is_published', true)
            ->forUser($college, $grade, $term, $planType)
            ->orderBy('start_datetime', 'desc')
            ->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'start_datetime' => 'required|date',
            'end_datetime' => 'required|date|after:start_datetime',
            'location' => 'required|string|max:255',
            'category' => 'required|string|in:conference,workshop,seminar,meeting',
            'image' => 'nullable|image|max:2048',
            'for_all_students' => 'required|boolean',
            'target_colleges' => 'required_if:for_all_students,false|array',
            'target_colleges.*' => 'string|in:law,islamic,political',
            'target_grades' => 'required_if:for_all_students,false|array',
            'target_grades.*' => 'integer|in:1,2,3,4',
            'target_terms' => 'required_if:for_all_students,false|array',
            'target_terms.*' => 'string|in:First,Second',
            'target_plans' => 'required_if:for_all_students,false|array',
            'target_plans.*' => 'string|in:free,basic,pro',
            'user_id' => 'required'
        ]);

        try {
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('events', 'public');
            }

            $event = Event::create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'start_datetime' => $validated['start_datetime'],
                'end_datetime' => $validated['end_datetime'],
                'location' => $validated['location'],
                'category' => $validated['category'],
                'image_path' => $imagePath,
                'target_colleges' => $validated['for_all_students'] ? null : $validated['target_colleges'],
                'target_grades' => $validated['for_all_students'] ? null : $validated['target_grades'],
                'target_terms' => $validated['for_all_students'] ? null : $validated['target_terms'],
                'target_plans' => $validated['for_all_students'] ? null : $validated['target_plans'],
                'for_all_students' => $validated['for_all_students'],
                'created_by' => $validated['user_id'],
            ]);

            return response()->json($event, 201);
        } catch (\Exception $e) {
            if (isset($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
            return response()->json(['message' => 'Event creation failed', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, Event $event)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'start_datetime' => 'required|date',
            'end_datetime' => 'required|date|after:start_datetime',
            'location' => 'required|string|max:255',
            'category' => 'required|string|in:conference,workshop,seminar,meeting',
            'image' => 'nullable|image|max:2048',
            'for_all_students' => 'required|boolean',
            'target_colleges' => 'required_if:for_all_students,false|array|min:1',
            'target_colleges.*' => 'string|in:law,islamic,political',
            'target_grades' => 'required_if:for_all_students,false|array|min:1',
            'target_grades.*' => 'integer|in:1,2,3,4',
            'target_terms' => 'required_if:for_all_students,false|array|min:1',
            'target_terms.*' => 'string|in:First,Second',
            'target_plans' => 'required_if:for_all_students,false|array|min:1',
            'target_plans.*' => 'string|in:free,basic,pro',
        ]);

        $imagePath = $event->image_path;
        if ($request->hasFile('image')) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = $request->file('image')->store('events', 'public');
        }

        $event->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'start_datetime' => $validated['start_datetime'],
            'end_datetime' => $validated['end_datetime'],
            'location' => $validated['location'],
            'category' => $validated['category'],
            'image_path' => $imagePath,
            'target_colleges' => $validated['for_all_students'] ? null : $validated['target_colleges'],
            'target_grades' => $validated['for_all_students'] ? null : $validated['target_grades'],
            'target_terms' => $validated['for_all_students'] ? null : $validated['target_terms'],
            'target_plans' => $validated['for_all_students'] ? null : $validated['target_plans'],
            'for_all_students' => $validated['for_all_students'],
        ]);

        return response()->json($event);
    }

    public function publish(Event $event)
    {
        $event->update(['is_published' => true]);
        return response()->json($event);
    }

    public function destroy(Event $event)
    {
        if ($event->image_path) {
            Storage::disk('public')->delete($event->image_path);
        }
        $event->delete();
        return response()->json(null, 204);
    }

    public function studentEvents(Request $request)
    {
        $request->validate([
            'college' => 'required|string|in:law,islamic,political',
            'grade' => 'required|integer|in:1,2,3,4',
            'term' => 'required|string|in:first,second',
            'plan_type' => 'required|string|in:free,basic,pro'
        ]);

        return Event::query()
            ->where('is_published', true)
            ->where(function($query) use ($request) {
                $query->where('for_all_students', true)
                    ->orWhere(function($q) use ($request) {
                        $q->whereJsonContains('target_colleges', $request->college)
                          ->whereJsonContains('target_grades', $request->grade)
                          ->whereJsonContains('target_terms', $request->term)
                          ->whereJsonContains('target_plans', $request->plan_type);
                    });
            })
            ->orderBy('start_datetime', 'asc')
            ->get();
    }
}
