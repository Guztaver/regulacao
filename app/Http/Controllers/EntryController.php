<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEntryRequest;
use App\Models\Entry;
use App\Models\EntryStatus;
use App\Models\EntryTimeline;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EntryController extends Controller
{
    public function store(StoreEntryRequest $request): JsonResponse
    {
        $validatedData = $request->validated();

        $entry = new Entry();
        $entry->patient_id = $validatedData['patient_id'];
        $entry->title = $validatedData['title'];
        $entry->created_by = Auth::id(); // Never null - auth is required via form request
        $entry->save();

        return response()->json(['message' => 'Entry created successfully', 'entry' => $entry], Response::HTTP_CREATED);
    }


    public function destroy(Request $request, $id): JsonResponse
    {
        // Ensure user is authenticated
        if (!Auth::check()) {
            return response()->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $entry = Entry::findOrFail($id);
        $entry->delete();

        return response()->json(['message' => 'Entry deleted successfully'], Response::HTTP_OK);
    }

    public function show(Request $request, $id): JsonResponse
    {
        // Ensure user is authenticated
        if (!Auth::check()) {
            return response()->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $entry = Entry::with(['patient', 'createdBy', 'currentStatus', 'statusTransitions.fromStatus', 'statusTransitions.toStatus', 'statusTransitions.user'])->findOrFail($id);

        return response()->json(['entry' => $entry], Response::HTTP_OK);
    }

    public function index(Request $request): JsonResponse
    {
        // Ensure user is authenticated
        if (!Auth::check()) {
            return response()->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $validatedData = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'patient_name' => 'nullable|string|max:255',
            'entry_id' => 'nullable|string|exists:entries,id',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $pendingStatuses = EntryStatus::where('is_final', false)->pluck('id');
        $query = Entry::with(['patient', 'createdBy', 'currentStatus', 'statusTransitions.fromStatus', 'statusTransitions.toStatus', 'statusTransitions.user'])
            ->whereIn('current_status_id', $pendingStatuses);

        // Filter by date range
        if (!empty($validatedData['date_from'])) {
            $query->whereDate('created_at', '>=', $validatedData['date_from']);
        }
        if (!empty($validatedData['date_to'])) {
            $query->whereDate('created_at', '<=', $validatedData['date_to']);
        }

        // Filter by patient name
        if (!empty($validatedData['patient_name'])) {
            $query->whereHas('patient', function ($q) use ($validatedData) {
                $q->where('name', 'LIKE', '%' . $validatedData['patient_name'] . '%');
            });
        }

        // Find by specific entry ID
        if (!empty($validatedData['entry_id'])) {
            $query->where('id', $validatedData['entry_id']);
        }

        // Limit results
        $limit = $validatedData['limit'] ?? 10; // Default to 10

        $entries = $query->latest('created_at')->limit($limit)->get();

        return response()->json($entries, JsonResponse::HTTP_OK);
    }

    public function complete(Request $request, $id): JsonResponse
    {
        // Ensure user is authenticated
        if (!Auth::check()) {
            return response()->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $entry = Entry::findOrFail($id);

        try {
            $entry->markAsCompleted();
            return response()->json(['message' => 'Entry completed successfully'], JsonResponse::HTTP_OK);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    public function completed(Request $request): JsonResponse
    {
        // Ensure user is authenticated
        if (!Auth::check()) {
            return response()->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $validatedData = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'patient_name' => 'nullable|string|max:255',
            'entry_id' => 'nullable|string|exists:entries,id',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $completedStatus = EntryStatus::findBySlug(EntryStatus::COMPLETED);
        $query = Entry::with(['patient', 'createdBy', 'currentStatus', 'statusTransitions.fromStatus', 'statusTransitions.toStatus', 'statusTransitions.user'])
            ->where('current_status_id', $completedStatus->id);

        // Filter by date range
        if (!empty($validatedData['date_from'])) {
            $query->whereDate('created_at', '>=', $validatedData['date_from']);
        }
        if (!empty($validatedData['date_to'])) {
            $query->whereDate('created_at', '<=', $validatedData['date_to']);
        }

        // Filter by patient name
        if (!empty($validatedData['patient_name'])) {
            $query->whereHas('patient', function ($q) use ($validatedData) {
                $q->where('name', 'LIKE', '%' . $validatedData['patient_name'] . '%');
            });
        }

        // Find by specific entry ID
        if (!empty($validatedData['entry_id'])) {
            $query->where('id', $validatedData['entry_id']);
        }

        // Limit results
        $limit = $validatedData['limit'] ?? 10; // Default to 10

        $entries = $query->latest('created_at')->limit($limit)->get();

        return response()->json([
            'entries' => $entries,
            'count' => $entries->count(),
            'filters' => [
                'date_from' => $validatedData['date_from'] ?? null,
                'date_to' => $validatedData['date_to'] ?? null,
                'patient_name' => $validatedData['patient_name'] ?? null,
                'entry_id' => $validatedData['entry_id'] ?? null,
                'limit' => $limit
            ]
        ], JsonResponse::HTTP_OK);
    }

    public function scheduleExam(Request $request, $id): JsonResponse
    {
        // Ensure user is authenticated
        if (!Auth::check()) {
            return response()->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $validatedData = $request->validate([
            'exam_scheduled_date' => 'required|date|after_or_equal:today',
        ]);

        $entry = Entry::findOrFail($id);

        try {
            $entry->scheduleExam($validatedData['exam_scheduled_date']);
            return response()->json(['message' => 'Exam scheduled successfully'], JsonResponse::HTTP_OK);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    public function markExamReady(Request $request, $id): JsonResponse
    {
        // Ensure user is authenticated
        if (!Auth::check()) {
            return response()->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $entry = Entry::findOrFail($id);

        try {
            $entry->markExamReady();
            return response()->json(['message' => 'Exam marked as ready'], JsonResponse::HTTP_OK);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    public function getStatuses(Request $request): JsonResponse
    {
        // Ensure user is authenticated
        if (!Auth::check()) {
            return response()->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $statuses = EntryStatus::active()->ordered()->get();

        return response()->json(['statuses' => $statuses], JsonResponse::HTTP_OK);
    }

    public function getNextStatuses(Request $request, $id): JsonResponse
    {
        // Ensure user is authenticated
        if (!Auth::check()) {
            return response()->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $entry = Entry::with('currentStatus')->findOrFail($id);
        $nextStatuses = $entry->getNextStatuses();

        return response()->json(['next_statuses' => $nextStatuses], JsonResponse::HTTP_OK);
    }

    public function transitionStatus(Request $request, $id): JsonResponse
    {
        // Ensure user is authenticated
        if (!Auth::check()) {
            return response()->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $validatedData = $request->validate([
            'status_id' => 'required|exists:entry_statuses,id',
            'reason' => 'nullable|string|max:500',
            'metadata' => 'nullable|array',
        ]);

        $entry = Entry::findOrFail($id);

        try {
            $entry->transitionTo(
                $validatedData['status_id'],
                $validatedData['reason'] ?? null,
                $validatedData['metadata'] ?? []
            );
            return response()->json(['message' => 'Status updated successfully'], JsonResponse::HTTP_OK);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    public function cancel(Request $request, $id): JsonResponse
    {
        // Ensure user is authenticated
        if (!Auth::check()) {
            return response()->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $validatedData = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $entry = Entry::findOrFail($id);

        try {
            $entry->cancel($validatedData['reason'] ?? null);
            return response()->json(['message' => 'Entry cancelled successfully'], JsonResponse::HTTP_OK);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }
}
