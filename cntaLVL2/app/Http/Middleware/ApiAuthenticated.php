<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthenticated
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Debug: Log what we're checking
        Log::info('ApiAuthenticated Middleware Check', [
            'url' => $request->url(),
            'session_id' => session()->getId(),
            'api_authenticated' => session('api_authenticated'),
            'session_has_data' => !empty(session()->all()),
            'all_session' => session()->all()
        ]);

        if (!session('api_authenticated')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'API authentication required',
                    'error_code' => 'NOT_AUTHENTICATED',
                    'debug' => [
                        'session_id' => session()->getId(),
                        'has_session_data' => !empty(session()->all())
                    ]
                ], 401);
            }

            return response()->view('auth.api-error', [
                'error_message' => 'API authentication required. Please use the proper authentication URL.',
                'error_code' => 'NOT_AUTHENTICATED'
            ], 401);
        }

        return $next($request);
    }
}