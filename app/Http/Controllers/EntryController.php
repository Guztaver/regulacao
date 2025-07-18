<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEntryRequest;
use App\Models\Entry;
use App\Models\EntryStatus;
use App\Models\EntryTimeline;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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

        $entry = Entry::with(['patient', 'createdBy', 'currentStatus', 'statusTransitions.fromStatus', 'statusTransitions.toStatus', 'statusTransitions.user', 'documents'])
            ->withCount('documents')
            ->findOrFail($id);

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
            'active_only' => 'nullable|boolean',
            'scheduled_only' => 'nullable|boolean',
        ]);

        $query = Entry::with(['patient', 'createdBy', 'currentStatus', 'statusTransitions.fromStatus', 'statusTransitions.toStatus', 'statusTransitions.user'])
            ->withCount('documents');

        // Filter by active entries (non-final status)
        if (!empty($validatedData['active_only'])) {
            $nonFinalStatuses = EntryStatus::where('is_final', false)->pluck('id');
            $query->whereIn('current_status_id', $nonFinalStatuses);
        }

        // Filter by scheduled entries (entries with scheduled_exam_date)
        if (!empty($validatedData['scheduled_only'])) {
            $query->whereHas('statusTransitions', function ($q) {
                $q->whereHas('toStatus', function ($statusQuery) {
                    $statusQuery->where('slug', EntryStatus::EXAM_SCHEDULED);
                })->whereNotNull('scheduled_date');
            });
        }

        // If no specific filter is applied, default to pending statuses (original behavior)
        if (empty($validatedData['active_only']) && empty($validatedData['scheduled_only'])) {
            $pendingStatuses = EntryStatus::where('is_final', false)->pluck('id');
            $query->whereIn('current_status_id', $pendingStatuses);
        }

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
            ->withCount('documents')
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
            'reason' => 'nullable|string|max:500',
        ]);

        $entry = Entry::findOrFail($id);

        try {
            $entry->scheduleExam(
                $validatedData['exam_scheduled_date'],
                $validatedData['reason'] ?? 'Exam scheduled'
            );

            return response()->json([
                'message' => 'Exam scheduled successfully',
                'entry' => $entry->fresh(['currentStatus', 'patient'])
            ], JsonResponse::HTTP_OK);
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

        $entry = Entry::with('currentStatus')->findOrFail($id);
        $newStatus = EntryStatus::findOrFail($validatedData['status_id']);

        // Debug logging
        Log::info('Status Transition Attempt', [
            'entry_id' => $id,
            'current_status' => $entry->currentStatus?->slug,
            'current_status_name' => $entry->currentStatus?->name,
            'new_status' => $newStatus->slug,
            'new_status_name' => $newStatus->name,
            'reason' => $validatedData['reason'] ?? null,
            'user_id' => Auth::id(),
        ]);

        try {
            $entry->transitionTo(
                $validatedData['status_id'],
                $validatedData['reason'] ?? null,
                $validatedData['metadata'] ?? []
            );

            Log::info('Status Transition Success', [
                'entry_id' => $id,
                'new_status' => $newStatus->slug,
            ]);

            return response()->json([
                'message' => 'Status updated successfully',
                'entry' => $entry->fresh(['currentStatus', 'statusTransitions'])
            ], JsonResponse::HTTP_OK);
        } catch (\InvalidArgumentException $e) {
            Log::error('Status Transition Failed', [
                'entry_id' => $id,
                'error' => $e->getMessage(),
                'current_status' => $entry->currentStatus?->slug,
                'target_status' => $newStatus->slug,
            ]);

            return response()->json(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            Log::error('Status Transition Exception', [
                'entry_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Internal server error during status transition'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
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

    public function active(Request $request): JsonResponse
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

        $nonFinalStatuses = EntryStatus::where('is_final', false)->pluck('id');
        $query = Entry::with(['patient', 'createdBy', 'currentStatus', 'statusTransitions.fromStatus', 'statusTransitions.toStatus', 'statusTransitions.user'])
            ->withCount('documents')
            ->whereIn('current_status_id', $nonFinalStatuses);

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
        $limit = $validatedData['limit'] ?? 50; // Default to 50

        $entries = $query->latest('created_at')->limit($limit)->get();

        return response()->json($entries, JsonResponse::HTTP_OK);
    }

    public function scheduled(Request $request): JsonResponse
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

        $query = Entry::with(['patient', 'createdBy', 'currentStatus', 'statusTransitions.fromStatus', 'statusTransitions.toStatus', 'statusTransitions.user'])
            ->withCount('documents')
            ->whereHas('statusTransitions', function ($q) {
                $q->whereNotNull('scheduled_date');
            });

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
        $limit = $validatedData['limit'] ?? 50; // Default to 50

        $entries = $query->latest('created_at')->limit($limit)->get();

        return response()->json($entries, JsonResponse::HTTP_OK);
    }

    public function print(Request $request, $id)
    {
        // Ensure user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $entry = Entry::with([
            'patient',
            'createdBy',
            'currentStatus',
            'statusTransitions.fromStatus',
            'statusTransitions.toStatus',
            'statusTransitions.user',
            'documents',
            'timeline'
        ])->findOrFail($id);

        return view('entries.print', compact('entry'));
    }
}
