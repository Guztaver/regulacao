<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PatientController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'sus_number' => 'nullable|string|size:15|unique:patients,sus_number',
        ]);

        $patient = new Patient();
        $patient->fill($validatedData);
        $patient->save();

        return response()->json(['message' => 'Patient created successfully', 'patient' => $patient], Response::HTTP_CREATED);
    }

    public function index(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'search' => 'nullable|string|max:255',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Patient::query();

        // Search functionality
        if (!empty($validatedData['search'])) {
            $search = $validatedData['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', '%' . $search . '%')
                  ->orWhere('email', 'LIKE', '%' . $search . '%')
                  ->orWhere('sus_number', 'LIKE', '%' . $search . '%');
            });
        }

        $limit = $validatedData['limit'] ?? 50;
        $patients = $query->withCount('documents')
                         ->withCount('entries')
                         ->latest('created_at')
                         ->limit($limit)
                         ->get();

        return response()->json($patients, Response::HTTP_OK);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $patient = Patient::findOrFail($id);
        $patient->delete();

        return response()->json(['message' => 'Patient deleted successfully'], Response::HTTP_OK);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $patient = Patient::with(['documents', 'entries.patient'])
                          ->withCount(['documents', 'entries'])
                          ->findOrFail($id);

        return response()->json([
            'patient' => $patient,
            'summary' => [
                'total_documents' => $patient->documents_count,
                'total_entries' => $patient->entries_count,
                'active_entries' => $patient->entries->where('completed', false)->count(),
                'completed_entries' => $patient->entries->where('completed', true)->count(),
            ]
        ], Response::HTTP_OK);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $patient = Patient::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:patients,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'sus_number' => 'nullable|string|size:15|unique:patients,sus_number,' . $id,
        ]);

        $patient->update($validatedData);

        return response()->json([
            'message' => 'Patient updated successfully',
            'patient' => $patient
        ], Response::HTTP_OK);
    }
}
