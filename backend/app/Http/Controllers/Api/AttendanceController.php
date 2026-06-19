<?php

namespace App\Http\Controllers\Api;

use App\Models\AttendanceSession;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AttendanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AttendanceSession::withCount('records');

        if ($request->has('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        $sessions = $query->latest()->get();
        return response()->json($sessions);
    }

    public function show(int $id): JsonResponse
    {
        $session = AttendanceSession::with(['course', 'records.student'])->findOrFail($id);
        return response()->json($session);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'session_date' => 'nullable|date',
            'status' => 'nullable|string|max:50',
        ]);

        $session = AttendanceSession::create(array_merge($validated, [
            'session_date' => $validated['session_date'] ?? now(),
            'status' => $validated['status'] ?? 'active',
        ]));

        return response()->json($session, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $session = AttendanceSession::findOrFail($id);
        $validated = $request->validate([
            'title' => 'string|max:255',
            'session_date' => 'nullable|date',
            'status' => 'nullable|string|max:50',
        ]);

        $session->update($validated);
        return response()->json($session);
    }

    public function destroy(int $id): JsonResponse
    {
        $session = AttendanceSession::findOrFail($id);
        $session->records()->delete();
        $session->delete();
        return response()->json(null, 204);
    }
}
