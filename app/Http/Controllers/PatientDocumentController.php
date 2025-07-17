<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PatientDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PatientDocumentController extends Controller
{
    public function store(Request $request, string $patientId): JsonResponse
    {
        $patient = Patient::findOrFail($patientId);

        $validatedData = $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'document_type' => 'required|string|in:' . implode(',', array_keys(PatientDocument::DOCUMENT_TYPES)),
            'description' => 'nullable|string|max:500',
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs('patient-documents/' . $patientId, $fileName, 'public');

        $document = PatientDocument::create([
            'patient_id' => $patientId,
            'original_name' => $originalName,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'document_type' => $validatedData['document_type'],
            'description' => $validatedData['description'],
        ]);

        return response()->json([
            'message' => 'Document uploaded successfully',
            'document' => $document->load('patient')
        ], JsonResponse::HTTP_CREATED);
    }

    public function index(string $patientId): JsonResponse
    {
        $patient = Patient::findOrFail($patientId);

        $documents = $patient->documents()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($document) {
                return [
                    'id' => $document->id,
                    'original_name' => $document->original_name,
                    'file_name' => $document->file_name,
                    'mime_type' => $document->mime_type,
                    'file_size' => $document->file_size,
                    'formatted_file_size' => $document->formatted_file_size,
                    'document_type' => $document->document_type,
                    'document_type_label' => $document->document_type_label,
                    'description' => $document->description,
                    'url' => $document->url,
                    'is_image' => $document->isImage(),
                    'is_pdf' => $document->isPdf(),
                    'created_at' => $document->created_at,
                ];
            });

        return response()->json([
            'patient' => $patient,
            'documents' => $documents
        ], JsonResponse::HTTP_OK);
    }

    public function show(string $patientId, string $documentId): JsonResponse
    {
        $patient = Patient::findOrFail($patientId);
        $document = $patient->documents()->findOrFail($documentId);

        return response()->json([
            'document' => [
                'id' => $document->id,
                'original_name' => $document->original_name,
                'file_name' => $document->file_name,
                'mime_type' => $document->mime_type,
                'file_size' => $document->file_size,
                'formatted_file_size' => $document->formatted_file_size,
                'document_type' => $document->document_type,
                'document_type_label' => $document->document_type_label,
                'description' => $document->description,
                'url' => $document->url,
                'is_image' => $document->isImage(),
                'is_pdf' => $document->isPdf(),
                'created_at' => $document->created_at,
            ],
            'patient' => $patient
        ], JsonResponse::HTTP_OK);
    }

    public function download(string $patientId, string $documentId)
    {
        $patient = Patient::findOrFail($patientId);
        $document = $patient->documents()->findOrFail($documentId);

        if (!Storage::disk('public')->exists($document->file_path)) {
            return response()->json(['message' => 'File not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        return Storage::disk('public')->download($document->file_path, $document->original_name);
    }

    public function destroy(string $patientId, string $documentId): JsonResponse
    {
        $patient = Patient::findOrFail($patientId);
        $document = $patient->documents()->findOrFail($documentId);

        $document->delete();

        return response()->json([
            'message' => 'Document deleted successfully'
        ], JsonResponse::HTTP_OK);
    }

    public function getDocumentTypes(): JsonResponse
    {
        return response()->json([
            'document_types' => PatientDocument::DOCUMENT_TYPES
        ], JsonResponse::HTTP_OK);
    }
}
