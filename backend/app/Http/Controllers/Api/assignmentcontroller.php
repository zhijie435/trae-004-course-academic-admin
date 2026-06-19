<?php

namespace App\Http\Controllers\Api;

use App\Models\Assignment;
use App\Models\Submission;
use App\Models\Enrollment;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AssignmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Assignment::withCount('submissions');

        if ($request->has('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        } else {
            $query->where('type', Assignment::TYPE_ROLL_CALL);
        }

        $assignments = $query->latest()->get();
        return response()->json($assignments);
    }

    public function show(int $id): JsonResponse
    {
        $assignment = Assignment::with([
            'course',
            'submissions' => function ($q) {
                $q->with('student');
            }
        ])->findOrFail($id);

        $enrolledStudentIds = Enrollment::where('course_id', $assignment->course_id)
            ->pluck('student_id')
            ->toArray();

        $submittedStudentIds = $assignment->submissions->pluck('student_id')->toArray();
        $missingStudentIds = array_diff($enrolledStudentIds, $submittedStudentIds);

        $missingSubmissions = collect($missingStudentIds)->map(function ($studentId) use ($assignment) {
            return [
                'id' => null,
                'assignment_id' => $assignment->id,
                'student_id' => $studentId,
                'student' => \App\Models\Student::find($studentId),
                'content' => null,
                'score' => null,
                'feedback' => null,
                'status' => Submission::STATUS_ABSENT,
                'submitted_at' => null,
                'graded_at' => null,
            ];
        });

        $allSubmissions = $assignment->submissions->merge($missingSubmissions);

        return response()->json([
            'id' => $assignment->id,
            'course_id' => $assignment->course_id,
            'title' => $assignment->title,
            'description' => $assignment->description,
            'type' => $assignment->type,
            'due_date' => $assignment->due_date,
            'max_score' => $assignment->max_score,
            'is_published' => $assignment->is_published,
            'created_at' => $assignment->created_at,
            'updated_at' => $assignment->updated_at,
            'course' => $assignment->course,
            'submissions' => $allSubmissions->values(),
            'stats' => [
                'total_enrolled' => count($enrolledStudentIds),
                'submitted' => count($submittedStudentIds),
                'missing' => count($missingStudentIds),
                'graded' => $assignment->submissions->where('status', Submission::STATUS_GRADED)->count(),
            ]
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|string|max:50',
            'due_date' => 'nullable|date',
            'max_score' => 'nullable|integer|min:0',
            'is_published' => 'nullable|boolean',
        ]);

        $validated['type'] = $validated['type'] ?? Assignment::TYPE_ROLL_CALL;
        $validated['max_score'] = $validated['max_score'] ?? 100;
        $validated['is_published'] = $validated['is_published'] ?? true;

        $assignment = Assignment::create($validated);
        return response()->json($assignment->loadCount('submissions'), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $assignment = Assignment::findOrFail($id);
        $validated = $request->validate([
            'title' => 'string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'max_score' => 'nullable|integer|min:0',
            'is_published' => 'nullable|boolean',
        ]);

        $assignment->update($validated);
        return response()->json($assignment->loadCount('submissions'));
    }

    public function destroy(int $id): JsonResponse
    {
        $assignment = Assignment::findOrFail($id);
        $assignment->submissions()->delete();
        $assignment->delete();
        return response()->json(null, 204);
    }
}
