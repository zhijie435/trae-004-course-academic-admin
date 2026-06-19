<?php

namespace App\Http\Controllers\Api;

use App\Models\Enrollment;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EnrollmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Enrollment::with(['course', 'student']);

        if ($request->has('course_id')) {
            $query->where('course_id', $request->course_id);
        }
        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        $enrollments = $query->get();
        return response()->json($enrollments);
    }

    public function show(int $id): JsonResponse
    {
        $enrollment = Enrollment::with(['course', 'student'])->findOrFail($id);
        return response()->json($enrollment);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'student_id' => 'required|exists:students,id',
            'status' => 'nullable|string|max:50',
        ]);

        $exists = Enrollment::where('course_id', $validated['course_id'])
            ->where('student_id', $validated['student_id'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => '该学生已报名此课程'], 409);
        }

        $enrollment = Enrollment::create(array_merge($validated, [
            'enrolled_at' => now(),
            'status' => $validated['status'] ?? 'enrolled',
        ]));

        return response()->json($enrollment->load(['course', 'student']), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $enrollment = Enrollment::findOrFail($id);
        $validated = $request->validate([
            'status' => 'nullable|string|max:50',
        ]);

        $enrollment->update($validated);
        return response()->json($enrollment->load(['course', 'student']));
    }

    public function destroy(int $id): JsonResponse
    {
        $enrollment = Enrollment::findOrFail($id);
        $enrollment->delete();
        return response()->json(null, 204);
    }
}
