<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PatientDocumentController extends Controller
{
    public function store(Request $request, string $patientId): JsonResponse
    {
        return response()->json([
            'error' => 'Patient document uploads are no longer supported. Please use entry documents instead.',
        ], Response::HTTP_NOT_FOUND);
    }

    public function index(string $patientId): JsonResponse
    {
        return response()->json([
            'patient' => Patient::findOrFail($patientId),
            'documents' => [],
        ], JsonResponse::HTTP_OK);
    }

    public function show(string $patientId, string $documentId): JsonResponse
    {
        return response()->json([
            'error' => 'Patient documents are no longer supported',
        ], Response::HTTP_NOT_FOUND);
    }

    public function download(string $patientId, string $documentId)
    {
        return response()->json([
            'error' => 'Patient documents are no longer supported',
        ], Response::HTTP_NOT_FOUND);
    }

    public function destroy(string $patientId, string $documentId): JsonResponse
    {
        return response()->json([
            'error' => 'Patient documents are no longer supported',
        ], Response::HTTP_METHOD_NOT_ALLOWED);
    }

    public function getDocumentTypes(): JsonResponse
    {
        return response()->json([
            'document_types' => [],
        ], JsonResponse::HTTP_OK);
    }
}
