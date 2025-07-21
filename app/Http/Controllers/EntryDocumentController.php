<?php

namespace App\Http\Controllers;

use App\Models\Entry;
use App\Models\EntryDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EntryDocumentController extends Controller
{
    public function store(Request $request, string $entryId): JsonResponse
    {
        // Ensure user is authenticated
        if (! Auth::check()) {
            return response()->json(['error' => 'Authentication required'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $entry = Entry::findOrFail($entryId);

        $validatedData = $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'document_type' => 'required|string|in:'.implode(',', array_keys(EntryDocument::DOCUMENT_TYPES)),
            'description' => 'nullable|string|max:500',
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $fileName = Str::uuid().'.'.$file->getClientOriginalExtension();
        $filePath = $file->storeAs('entry-documents/'.$entryId, $fileName, 'public');

        $document = EntryDocument::create([
            'entry_id' => $entryId,
            'original_name' => $originalName,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'document_type' => $validatedData['document_type'],
            'description' => $validatedData['description'],
            'uploaded_by' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'Documento enviado com sucesso',
            'document' => $document->load('entry'),
        ], JsonResponse::HTTP_CREATED);
    }

    public function index(string $entryId): JsonResponse
    {
        // Ensure user is authenticated
        if (! Auth::check()) {
            return response()->json(['error' => 'Authentication required'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $entry = Entry::findOrFail($entryId);

        $documents = $entry->documents()
            ->with('uploadedBy')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($document) => [
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
                'uploaded_by' => $document->uploadedBy?->name,
                'created_at' => $document->created_at,
            ]);

        return response()->json([
            'entry' => $entry,
            'documents' => $documents,
        ], JsonResponse::HTTP_OK);
    }

    public function show(string $entryId, string $documentId): JsonResponse
    {
        // Ensure user is authenticated
        if (! Auth::check()) {
            return response()->json(['error' => 'Authentication required'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $entry = Entry::findOrFail($entryId);
        $document = $entry->documents()->with('uploadedBy')->findOrFail($documentId);

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
                'uploaded_by' => $document->uploadedBy?->name,
                'created_at' => $document->created_at,
            ],
            'entry' => $entry,
        ], JsonResponse::HTTP_OK);
    }

    public function download(string $entryId, string $documentId)
    {
        // Ensure user is authenticated
        if (! Auth::check()) {
            return response()->json(['error' => 'Authentication required'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $entry = Entry::findOrFail($entryId);
        $document = $entry->documents()->findOrFail($documentId);

        if (! Storage::disk('public')->exists($document->file_path)) {
            return response()->json(['message' => 'File not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        return Storage::download('public/'.$document->file_path, $document->original_name);
    }

    public function destroy(string $entryId, string $documentId): JsonResponse
    {
        // Ensure user is authenticated
        if (! Auth::check()) {
            return response()->json(['error' => 'Authentication required'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $entry = Entry::findOrFail($entryId);
        $document = $entry->documents()->findOrFail($documentId);

        $document->delete();

        return response()->json([
            'message' => 'Documento excluído com sucesso',
        ], JsonResponse::HTTP_OK);
    }

    public function getDocumentTypes(): JsonResponse
    {
        // Ensure user is authenticated
        if (! Auth::check()) {
            return response()->json(['error' => 'Authentication required'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        return response()->json([
            'document_types' => EntryDocument::DOCUMENT_TYPES,
        ], JsonResponse::HTTP_OK);
    }
}
