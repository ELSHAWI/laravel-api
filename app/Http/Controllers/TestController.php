<?php

namespace App\Http\Controllers;

use App\Models\Test;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function index()
    {
        $tests = Test::with(['questions', 'questions.options'])->get();
        return response()->json($tests);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title.en' => 'required|string',
            'title.ar' => 'required|string',
            'description.en' => 'nullable|string',
            'description.ar' => 'nullable|string',
            'duration' => 'required|integer|min:1',
            'difficulty' => 'required|in:Beginner,Intermediate,Advanced',
            'college' => 'required|in:islamic,law,political',
            'grade' => 'required|integer|in:1,2,3,4',
            'term' => 'required|in:First,Second',
            'plan_type' => 'required|in:free,basic,pro',
            'questions' => 'required|array|min:1',
            'questions.*.text.en' => 'required|string',
            'questions.*.text.ar' => 'required|string',
            'questions.*.type' => 'required|in:mcq,written',
            'questions.*.points' => 'required|integer|min:1',
            'questions.*.options' => 'required_if:questions.*.type,mcq|array',
            'questions.*.options.*.en' => 'required_if:questions.*.type,mcq|string',
            'questions.*.options.*.ar' => 'required_if:questions.*.type,mcq|string',
            'questions.*.correctAnswer' => 'required_if:questions.*.type,mcq|integer'
        ]);

        // Create the test
        $test = Test::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'duration' => $validated['duration'],
            'difficulty' => $validated['difficulty'],
            'college' => $validated['college'],
            'grade' => $validated['grade'],
            'term' => $validated['term'],
            'plan_type' => $validated['plan_type']
        ]);

        // Create questions
        foreach ($validated['questions'] as $questionData) {
            $question = Question::create([
                'test_id' => $test->id,
                'text' => $questionData['text'],
                'type' => $questionData['type'],
                'points' => $questionData['points']
            ]);

            // Create options for MCQ questions
            if ($questionData['type'] === 'mcq') {
                foreach ($questionData['options'] as $index => $optionData) {
                    QuestionOption::create([
                        'question_id' => $question->id,
                        'text' => $optionData,
                        'is_correct' => $index === $questionData['correctAnswer']
                    ]);
                }
            }
        }

        return response()->json($test->load(['questions', 'questions.options']), 201);
    }

    public function destroy($id)
    {
        $test = Test::findOrFail($id);
        $test->delete();
        return response()->json(null, 204);
    }
}