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

    public function index(): JsonResponse
        {
            $entries = Entry::with('patient')
                           ->where('completed', false)
                           ->get();

            return response()->json($entries, Response::HTTP_OK);
        }

    public function complete(Request $request, $id): JsonResponse
    {
        $entry = Entry::findOrFail($id);
        $entry->toggleCompleted();
        $entry->save();

        return response()->json(['message' => 'Entry completed successfully'], Response::HTTP_OK);
    }
}
