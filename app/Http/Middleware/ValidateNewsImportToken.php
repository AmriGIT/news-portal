<?php

namespace App\Http\Middleware;

use App\Services\NewsImportTokenService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateNewsImportToken
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('news-import.enabled', true)) {
            return response()->json([
                'success' => false,
                'message' => 'Import berita sedang dinonaktifkan.',
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        $bearerToken = $request->bearerToken();

        if (blank($bearerToken)) {
            return response()->json([
                'success' => false,
                'message' => 'Import token diperlukan.',
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $token = app(NewsImportTokenService::class)->findUsableToken($bearerToken);

        if ($token === null) {
            return response()->json([
                'success' => false,
                'message' => 'Import token tidak valid.',
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        if (! $token->can('news:import')) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak memiliki izin yang diperlukan.',
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        $request->attributes->set('import_token', $token);

        return $next($request);
    }
}
