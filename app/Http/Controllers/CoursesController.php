<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CoursesController extends Controller
{
    // Define allowed values for enum-like fields
    protected $allowedColleges = ['islamic', 'law', 'political'];
    protected $allowedGrades = ['1', '2', '3', '4'];
    protected $allowedTerms = ['first', 'second'];

    // Store a new course
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:500',
            'teacher' => 'nullable|string|max:255',
            'video' => 'nullable|file|mimes:mp4,avi,mpeg,mov,webm|max:51200',
            'summary' => 'nullable|file|mimes:pdf|max:10240',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
            'for_who' => 'nullable|string|max:500',
            'term' => ['nullable', 'string', Rule::in($this->allowedTerms)],
            'grade' => ['nullable', 'string', Rule::in($this->allowedGrades)],
            'college' => ['nullable', 'string', Rule::in($this->allowedColleges)],
            'description' => 'nullable|string',
            'plan_type' => 'required|string|in:free,basic,pro'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        try {
            // Handle file uploads with transaction
            return \DB::transaction(function () use ($data, $request) {
                if ($request->hasFile('video')) {
                    $data['video_path'] = $request->file('video')->store('videos', 'public');
                }

                if ($request->hasFile('summary')) {
                    $data['summary_path'] = $request->file('summary')->store('summaries', 'public');
                }

                if ($request->hasFile('image')) {
                    $data['image'] = $request->file('image')->store('courses_images', 'public');
                }

                $course = Course::create($data);
                
                return response()->json([
                    'success' => true,
                    'data' => $course,
                ], 201);
            });
        } catch (\Exception $e) {
            // Cleanup any uploaded files if transaction fails
            if (isset($data['video_path'])) {
                Storage::disk('public')->delete($data['video_path']);
            }
            if (isset($data['summary_path'])) {
                Storage::disk('public')->delete($data['summary_path']);
            }
            if (isset($data['image'])) {
                Storage::disk('public')->delete($data['image']);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to create course',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get all courses (paginated)
    public function index()
    {
        $courses = Course::all();

        return response()->json([
            'success' => true,
            'data' => $courses,
        ]);
    }

    // Get filtered courses
    public function filter(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'term' => ['nullable', 'string', Rule::in($this->allowedTerms)],
            'grade' => ['nullable', 'string', Rule::in($this->allowedGrades)],
            'college' => ['nullable', 'string', Rule::in($this->allowedColleges)],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $query = Course::query();

        if ($request->term) {
            $query->where('term', $request->term);
        }

        if ($request->grade) {
            $query->where('grade', $request->grade);
        }

        if ($request->college) {
            $query->where('college', $request->college);
        }

        $courses = $query->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $courses,
        ]);
    }

    // Show single course
    public function show($id)
    {
        $course = Course::find($id);

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'Course not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $course,
        ]);
    }

    // Update a course
    public function update(Request $request, $id)
    {
        $course = Course::find($id);

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'Course not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'subtitle' => 'nullable|string|max:500',
            'teacher' => 'nullable|string|max:255',
            'video' => 'nullable|file|mimes:mp4,avi,mpeg,mov,webm|max:51200',
            'summary' => 'nullable|file|mimes:pdf|max:10240',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
            'for_who' => 'nullable|string|max:500',
            'term' => ['nullable', 'string', Rule::in($this->allowedTerms)],
            'grade' => ['nullable', 'string', Rule::in($this->allowedGrades)],
            'college' => ['nullable', 'string', Rule::in($this->allowedColleges)],
            'description' => 'nullable|string',
            'plan_type' => 'required|string|in:free,basic,pro'

        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        try {
            return \DB::transaction(function () use ($data, $request, $course) {
                $oldFiles = [
                    'video_path' => $course->video_path,
                    'summary_path' => $course->summary_path,
                    'image' => $course->image
                ];

                if ($request->hasFile('video')) {
                    $data['video_path'] = $request->file('video')->store('videos', 'public');
                }

                if ($request->hasFile('summary')) {
                    $data['summary_path'] = $request->file('summary')->store('summaries', 'public');
                }

                if ($request->hasFile('image')) {
                    $data['image'] = $request->file('image')->store('courses_images', 'public');
                }

                $course->update($data);

                // Delete old files only after successful update
                if (isset($data['video_path']) && $oldFiles['video_path']) {
                    Storage::disk('public')->delete($oldFiles['video_path']);
                }
                if (isset($data['summary_path']) && $oldFiles['summary_path']) {
                    Storage::disk('public')->delete($oldFiles['summary_path']);
                }
                if (isset($data['image']) && $oldFiles['image']) {
                    Storage::disk('public')->delete($oldFiles['image']);
                }

                return response()->json([
                    'success' => true,
                    'data' => $course,
                ]);
            });
        } catch (\Exception $e) {
            // Cleanup any new files if update fails
            if (isset($data['video_path'])) {
                Storage::disk('public')->delete($data['video_path']);
            }
            if (isset($data['summary_path'])) {
                Storage::disk('public')->delete($data['summary_path']);
            }
            if (isset($data['image'])) {
                Storage::disk('public')->delete($data['image']);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to update course',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Delete a course
    public function destroy($id)
    {
        $course = Course::find($id);

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'Course not found'
            ], 404);
        }

        try {
            \DB::transaction(function () use ($course) {
                if ($course->video_path) {
                    Storage::disk('public')->delete($course->video_path);
                }

                if ($course->summary_path) {
                    Storage::disk('public')->delete($course->summary_path);
                }

                if ($course->image) {
                    Storage::disk('public')->delete($course->image);
                }

                $course->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Course deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete course',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}