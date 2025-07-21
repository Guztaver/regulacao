<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientRequest;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PatientController extends Controller
{
    public function store(StorePatientRequest $request): JsonResponse
    {
        $validatedData = $request->validated();

        $patient = new Patient;
        $patient->fill($validatedData);
        $patient->created_by = Auth::id(); // Never null - auth is required via form request
        $patient->save();

        return response()->json(['message' => 'Paciente criado com sucesso', 'patient' => $patient], Response::HTTP_CREATED);
    }

    public function index(Request $request): JsonResponse
    {
        // Ensure user is authenticated
        if (! Auth::check()) {
            return response()->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $validatedData = $request->validate([
            'search' => 'nullable|string|max:255',
            'limit' => 'nullable|integer|min:1|max:1000',
        ]);

        $query = Patient::query();

        // Search functionality
        if (! empty($validatedData['search'])) {
            $search = $validatedData['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', '%'.$search.'%')
                    ->orWhere('email', 'LIKE', '%'.$search.'%')
                    ->orWhere('sus_number', 'LIKE', '%'.$search.'%');
            });
        }

        $limit = $validatedData['limit'] ?? 50;
        $patients = $query->with('createdBy')
            ->withCount('entries')
            ->latest('created_at')
            ->limit($limit)
            ->get();

        return response()->json($patients, Response::HTTP_OK);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        // Ensure user is authenticated
        if (! Auth::check()) {
            return response()->json(['error' => 'Autenticação obrigatória'], Response::HTTP_UNAUTHORIZED);
        }

        $patient = Patient::findOrFail($id);
        $patient->delete();

        return response()->json(['message' => 'Paciente excluído com sucesso'], Response::HTTP_OK);
    }

    public function show(Request $request, $id): JsonResponse
    {
        // Ensure user is authenticated
        if (! Auth::check()) {
            return response()->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $patient = Patient::with(['entries.patient', 'createdBy'])
            ->withCount(['entries'])
            ->findOrFail($id);

        return response()->json([
            'patient' => $patient,
            'summary' => [
                'total_entries' => $patient->entries_count,
                'active_entries' => $patient->entries->where('completed', false)->count(),
                'completed_entries' => $patient->entries->where('completed', true)->count(),
            ],
        ], Response::HTTP_OK);
    }

    public function update(Request $request, $id): JsonResponse
    {
        // Ensure user is authenticated
        if (! Auth::check()) {
            return response()->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $patient = Patient::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'sus_number' => 'nullable|string|size:15|unique:patients,sus_number,'.$id,
        ]);

        $patient->update($validatedData);

        return response()->json([
            'message' => 'Paciente atualizado com sucesso',
            'patient' => $patient,
        ], Response::HTTP_OK);
    }
}
