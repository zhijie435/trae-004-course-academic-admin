<?php

namespace App\Http\Controllers\Api;

use App\Models\Student;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StudentController extends Controller
{
    public function index(): JsonResponse
    {
        $students = Student::withCount('enrollments')->get();
        return response()->json($students);
    }

    public function show(int $id): JsonResponse
    {
        $student = Student::with([
            'enrollments.course',
            'submissions.assignment'
        ])->findOrFail($id);
        return response()->json($student);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'student_no' => 'required|string|max:50|unique:students',
            'email' => 'nullable|email|unique:students',
            'phone' => 'nullable|string|max:20',
            'class_name' => 'nullable|string|max:100',
        ]);

        $student = Student::create($validated);
        return response()->json($student, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $student = Student::findOrFail($id);
        $validated = $request->validate([
            'name' => 'string|max:255',
            'student_no' => 'string|max:50|unique:students,student_no,' . $id,
            'email' => 'nullable|email|unique:students,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'class_name' => 'nullable|string|max:100',
        ]);

        $student->update($validated);
        return response()->json($student);
    }

    public function destroy(int $id): JsonResponse
    {
        $student = Student::findOrFail($id);
        $student->delete();
        return response()->json(null, 204);
    }
}
