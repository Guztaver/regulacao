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
        ]);

        $patient = new Patient();
        $patient->fill($validatedData);
        $patient->save();

        return response()->json(['message' => 'Patient created successfully', 'patient' => $patient], Response::HTTP_CREATED);
    }

    public function index(Request $request): JsonResponse
    {
        $patients = Patient::all();

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
        $patient = Patient::findOrFail($id);

        return response()->json(['patient' => $patient], Response::HTTP_OK);
    }
}
