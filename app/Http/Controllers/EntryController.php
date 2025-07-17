<?php

namespace App\Http\Controllers;

use App\Models\Entry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EntryController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'title' => 'required|string|max:255',
        ]);

        $entry = new Entry();
        $entry->patient_id = $validatedData['patient_id'];
        $entry->title = $validatedData['title'];
        $entry->save();

        return response()->json(['message' => 'Entry created successfully', 'entry' => $entry], Response::HTTP_CREATED);
    }


    public function destroy(Request $request, $id): JsonResponse
    {
        $entry = Entry::findOrFail($id);
        $entry->delete();

        return response()->json(['message' => 'Entry deleted successfully'], Response::HTTP_OK);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $entry = Entry::findOrFail($id);

        return response()->json(['entry' => $entry], Response::HTTP_OK);
    }

    public function index(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'patient_name' => 'nullable|string|max:255',
            'entry_id' => 'nullable|string|exists:entries,id',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Entry::with('patient')->where('completed', false);

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
        $entry = Entry::findOrFail($id);
        $entry->toggleCompleted();
        $entry->save();

        return response()->json(['message' => 'Entry completed successfully'], JsonResponse::HTTP_OK);
    }

    public function completed(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'patient_name' => 'nullable|string|max:255',
            'entry_id' => 'nullable|string|exists:entries,id',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Entry::with('patient')->where('completed', true);

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
}
