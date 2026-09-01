<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $query = Document::with('uploader');

        if ($request->filled('documentable_type') && $request->filled('documentable_id')) {
            $query->where('documentable_type', $request->string('documentable_type'))
                ->where('documentable_id', $request->string('documentable_id'));
        }

        return DocumentResource::collection($query->latest()->paginate($request->integer('per_page', 20)));
    }

    public function store(StoreDocumentRequest $request)
    {
        $path = $request->file('file')->store('documents', 'private');

        $document = Document::create([
            'documentable_type' => $request->validated('documentable_type'),
            'documentable_id' => $request->validated('documentable_id'),
            'file_path' => $path,
            'original_name' => $request->file('file')->getClientOriginalName(),
            'uploaded_by' => $request->user()->id,
        ]);

        return new DocumentResource($document->load('uploader'));
    }

    public function show(Document $document)
    {
        return new DocumentResource($document->load('uploader'));
    }

    public function destroy(Document $document)
    {
        Storage::disk('private')->delete($document->file_path);
        $document->delete();

        return response()->noContent();
    }
}
