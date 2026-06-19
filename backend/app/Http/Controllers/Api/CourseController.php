<?php

namespace App\Http\Controllers\Api;

use App\Models\Course;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CourseController extends Controller
{
    public function index(): JsonResponse
    {
        $courses = Course::withCount(['enrollments', 'assignments'])->get();
        return response()->json($courses);
    }

    public function show(int $id): JsonResponse
    {
        $course = Course::with([
            'enrollments.student',
            'assignments' => function ($q) {
                $q->where('type', 'roll_call')->latest();
            }
        ])->findOrFail($id);

        return response()->json($course);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:courses',
            'description' => 'nullable|string',
            'teacher_name' => 'nullable|string|max:255',
            'semester' => 'nullable|string|max:50',
        ]);

        $course = Course::create($validated);
        return response()->json($course, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $course = Course::findOrFail($id);
        $validated = $request->validate([
            'name' => 'string|max:255',
            'code' => 'string|max:50|unique:courses,code,' . $id,
            'description' => 'nullable|string',
            'teacher_name' => 'nullable|string|max:255',
            'semester' => 'nullable|string|max:50',
        ]);

        $course->update($validated);
        return response()->json($course);
    }

    public function destroy(int $id): JsonResponse
    {
        $course = Course::findOrFail($id);
        $course->delete();
        return response()->json(null, 204);
    }
}
