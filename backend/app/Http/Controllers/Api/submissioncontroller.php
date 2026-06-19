<?php

namespace App\Http\Controllers\Api;

use App\Models\Submission;
use App\Models\Assignment;
use App\Models\Enrollment;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SubmissionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Submission::with(['assignment', 'student']);

        if ($request->has('assignment_id')) {
            $query->where('assignment_id', $request->assignment_id);
        }
        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $submissions = $query->get();
        return response()->json($submissions);
    }

    public function show(int $id): JsonResponse
    {
        $submission = Submission::with(['assignment', 'student'])->findOrFail($id);
        return response()->json($submission);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'assignment_id' => 'required|exists:assignments,id',
            'student_id' => 'required|exists:students,id',
            'content' => 'nullable|string',
        ]);

        $assignment = Assignment::findOrFail($validated['assignment_id']);

        $enrolled = Enrollment::where('course_id', $assignment->course_id)
            ->where('student_id', $validated['student_id'])
            ->exists();

        if (!$enrolled) {
            return response()->json(['message' => '该学生未报名此课程'], 403);
        }

        $existing = Submission::where('assignment_id', $validated['assignment_id'])
            ->where('student_id', $validated['student_id'])
            ->first();

        if ($existing) {
            return response()->json(['message' => '该学生已提交过此作业'], 409);
        }

        $submission = Submission::create(array_merge($validated, [
            'status' => Submission::STATUS_PENDING,
            'submitted_at' => now(),
        ]));

        return response()->json($submission->load(['assignment', 'student']), 201);
    }

    public function grade(Request $request, int $id): JsonResponse
    {
        $submission = Submission::findOrFail($id);

        $validated = $request->validate([
            'score' => 'nullable|integer|min:0',
            'feedback' => 'nullable|string',
            'status' => 'nullable|in:' . implode(',', [
                Submission::STATUS_GRADED,
                Submission::STATUS_PENDING,
                Submission::STATUS_ABSENT,
            ]),
        ]);

        if (isset($validated['score'])) {
            $assignment = $submission->assignment;
            if ($assignment && $assignment->max_score !== null) {
                if ($validated['score'] > $assignment->max_score) {
                    return response()->json([
                        'message' => "分数不能超过满分 {$assignment->max_score}"
                    ], 422);
                }
            }
        }

        $status = $validated['status'] ?? Submission::STATUS_GRADED;

        $updateData = [
            'status' => $status,
            'graded_at' => now(),
        ];

        if (isset($validated['score'])) {
            $updateData['score'] = $validated['score'];
        }
        if (isset($validated['feedback'])) {
            $updateData['feedback'] = $validated['feedback'];
        }

        $submission->update($updateData);
        return response()->json($submission->load(['assignment', 'student']));
    }

    public function markAbsent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'assignment_id' => 'required|exists:assignments,id',
            'student_id' => 'required|exists:students,id',
            'feedback' => 'nullable|string',
        ]);

        $assignment = Assignment::findOrFail($validated['assignment_id']);

        $existing = Submission::where('assignment_id', $validated['assignment_id'])
            ->where('student_id', $validated['student_id'])
            ->first();

        if ($existing) {
            $existing->update([
                'status' => Submission::STATUS_ABSENT,
                'score' => 0,
                'feedback' => $validated['feedback'] ?? $existing->feedback,
                'graded_at' => now(),
            ]);
            return response()->json($existing->load(['assignment', 'student']));
        }

        $submission = Submission::create([
            'assignment_id' => $validated['assignment_id'],
            'student_id' => $validated['student_id'],
            'content' => null,
            'score' => 0,
            'feedback' => $validated['feedback'] ?? '缺勤',
            'status' => Submission::STATUS_ABSENT,
            'submitted_at' => null,
            'graded_at' => now(),
        ]);

        return response()->json($submission->load(['assignment', 'student']), 201);
    }

    public function batchGrade(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'assignment_id' => 'required|exists:assignments,id',
            'grades' => 'required|array',
            'grades.*.student_id' => 'required|exists:students,id',
            'grades.*.score' => 'nullable|integer|min:0',
            'grades.*.feedback' => 'nullable|string',
            'grades.*.status' => 'nullable|in:graded,pending,absent',
        ]);

        $assignment = Assignment::findOrFail($validated['assignment_id']);
        $results = [];

        foreach ($validated['grades'] as $gradeData) {
            $submission = Submission::where('assignment_id', $validated['assignment_id'])
                ->where('student_id', $gradeData['student_id'])
                ->first();

            $status = $gradeData['status'] ?? Submission::STATUS_GRADED;

            if (!$submission) {
                if ($status === Submission::STATUS_ABSENT) {
                    $submission = Submission::create([
                        'assignment_id' => $validated['assignment_id'],
                        'student_id' => $gradeData['student_id'],
                        'score' => $gradeData['score'] ?? 0,
                        'feedback' => $gradeData['feedback'] ?? '缺勤',
                        'status' => Submission::STATUS_ABSENT,
                        'graded_at' => now(),
                    ]);
                } else {
                    continue;
                }
            } else {
                if (isset($gradeData['score']) && $assignment->max_score !== null) {
                    if ($gradeData['score'] > $assignment->max_score) {
                        continue;
                    }
                }

                $updateData = [
                    'status' => $status,
                    'graded_at' => now(),
                ];
                if (isset($gradeData['score'])) {
                    $updateData['score'] = $gradeData['score'];
                }
                if (isset($gradeData['feedback'])) {
                    $updateData['feedback'] = $gradeData['feedback'];
                }

                $submission->update($updateData);
            }

            $results[] = $submission->load(['assignment', 'student']);
        }

        return response()->json(['graded_count' => count($results), 'results' => $results]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $submission = Submission::findOrFail($id);
        $validated = $request->validate([
            'content' => 'nullable|string',
            'score' => 'nullable|integer|min:0',
            'feedback' => 'nullable|string',
            'status' => 'nullable|in:graded,pending,absent',
        ]);

        if (isset($validated['score'])) {
            $assignment = $submission->assignment;
            if ($assignment && $assignment->max_score !== null && $validated['score'] > $assignment->max_score) {
                return response()->json(['message' => "分数不能超过满分 {$assignment->max_score}"], 422);
            }
        }

        if (isset($validated['status']) && $validated['status'] === Submission::STATUS_GRADED && !$submission->graded_at) {
            $validated['graded_at'] = now();
        }

        $submission->update($validated);
        return response()->json($submission->load(['assignment', 'student']));
    }

    public function destroy(int $id): JsonResponse
    {
        $submission = Submission::findOrFail($id);
        $submission->delete();
        return response()->json(null, 204);
    }
}
