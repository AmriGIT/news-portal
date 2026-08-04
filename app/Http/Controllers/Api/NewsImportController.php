<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportNewsRequest;
use App\Models\NewsImport;
use App\Services\NewsImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NewsImportController extends Controller
{
    public function store(ImportNewsRequest $request, NewsImportService $imports): JsonResponse
    {
        $token = $request->attributes->get('import_token');
        $publishMode = $request->publishMode();

        if ($publishMode === 'published' && (! config('news-import.allow_publish', true) || ! $token->can('news:publish'))) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak memiliki izin untuk mempublikasikan artikel.',
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        $file = $request->file('package');
        $filename = Str::uuid().'.zip';
        $path = $file->storeAs('imports/api/uploads', $filename, 'local');

        $result = $imports->import(
            zipPath: Storage::disk('local')->path($path),
            originalFilename: $file->getClientOriginalName(),
            publishMode: $publishMode,
            token: $token,
            idempotencyKey: $request->header('Idempotency-Key'),
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return response()->json($result['payload'], $result['status_code']);
    }

    public function show(Request $request, NewsImport $newsImport): JsonResponse
    {
        $token = $request->attributes->get('import_token');

        if ($token && $newsImport->import_token_id !== $token->id) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak memiliki izin yang diperlukan.',
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        return response()->json([
            'success' => true,
            'import_id' => $newsImport->uuid,
            'status' => $newsImport->status->value,
            'requested_publish_mode' => $newsImport->requested_publish_mode,
            'total' => $newsImport->total_items,
            'imported' => $newsImport->imported_items,
            'failed' => $newsImport->failed_items,
            'warnings' => $newsImport->warnings ?: [],
            'errors' => $newsImport->items()
                ->whereNotNull('validation_errors')
                ->get(['slug', 'validation_errors'])
                ->map(fn ($item): array => [
                    'slug' => $item->slug,
                    'message' => implode(' ', $item->validation_errors ?: []),
                ])
                ->all(),
        ]);
    }
}
