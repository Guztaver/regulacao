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
            return response()->json(['error' => 'Autenticação obrigatória'], Response::HTTP_UNAUTHORIZED);
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
            return response()->json(['error' => 'Autenticação obrigatória'], Response::HTTP_UNAUTHORIZED);
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
            return response()->json(['error' => 'Autenticação obrigatória'], Response::HTTP_UNAUTHORIZED);
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

    /**
     * Lookup patient entries by SUS number (public endpoint, no auth required)
     */
    public function lookupBySusNumber(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'sus_number' => 'required|string|size:15',
        ]);

        $patient = Patient::where('sus_number', $validatedData['sus_number'])
            ->with([
                'entries.currentStatus',
                'entries.statusTransitions' => function ($query) {
                    $query->with(['fromStatus', 'toStatus'])->latest()->limit(5);
                }
            ])
            ->first();

        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhum paciente encontrado com este número do SUS'
            ], Response::HTTP_NOT_FOUND);
        }

        // Filter out sensitive information and only return patient-relevant data
        $publicData = [
            'success' => true,
            'patient' => [
                'name' => $patient->name,
                'sus_number' => $patient->sus_number,
                'entries_count' => $patient->entries->count(),
            ],
            'entries' => $patient->entries->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'title' => $entry->title,
                    'current_status' => [
                        'name' => $entry->currentStatus?->name ?? 'Status não definido',
                        'slug' => $entry->currentStatus?->slug ?? 'undefined',
                        'color' => $entry->currentStatus?->color ?? 'gray',
                    ],
                    'created_at' => $entry->created_at ? $entry->created_at->format('d/m/Y H:i') : null,
                    'scheduled_exam_date' => $entry->scheduled_exam_date ?
                        \Carbon\Carbon::parse($entry->scheduled_exam_date)->format('d/m/Y') : null,
                    'recent_transitions' => $entry->statusTransitions->take(3)->map(function ($transition) {
                        return [
                            'from_status' => $transition->fromStatus?->name,
                            'to_status' => $transition->toStatus?->name ?? 'Status não definido',
                            'performed_at' => $transition->transitioned_at ? $transition->transitioned_at->format('d/m/Y H:i') : null,
                            'reason' => $transition->reason,
                        ];
                    }),
                ];
            }),
        ];

        return response()->json($publicData, Response::HTTP_OK);
    }
}
