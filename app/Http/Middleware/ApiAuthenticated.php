<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        
        Log::info('ApiAuthenticated Middleware Check', [
            'url' => $request->fullUrl(),
            'session_id' => session()->getId(),
            'authenticated_user_uin' => session('authenticated_user_uin'),
        ]);

        // Already authenticated → skip API
        if (session()->has('authenticated_user_uin')) {
            session(['api_authenticated' => true]);
            return $next($request);
        }

        // Get token from URL
        $token = $request->query('token');
      //  $token ='eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0L3BhcnRha2VkaWdpdGFsIiwiYXVkIjoiaHR0cDovL2xvY2FsaG9zdC9wYXJ0YWtlZGlnaXRhbCIsImlhdCI6MTc3MTIyMjg5NywiYWNjZXNzX3Rva2VuIjoiNzFkZTkxM2E4ZWRmYmY4MGFmMDBmODY2YmMwYzU3IiwiVXNlcl9VSU4iOiIxNzY2MDYxNTcyIn0.n8SCgiNZo5dlWcAucKbR2kkTuirWBV9iCuqogm4XINM';

        if (!$token) {
            return $this->unauthorized($request);
        }

        try {
            // Call Partakers API
            $response = Http::timeout(10)->post(
                config('services.partakers.user_api'),
                ['token' => $token]
            );
            

            if (!$response->ok()) {
                return response()->view('errors.sessionexpires', [], 401);
            }

            $data = $response->json();

            if (empty($data['data'][0]['User_UIN'])) {
                return $this->unauthorized($request);
            }

            // Save session
            session([
                'token' => $token,
                'authenticated_user_uin' => $data['data'][0]['User_UIN'],
                'user_data' => $data['data'][0],
                'Regi_Addr' => $data['data'][0]['Regi_Addr'] ?? null,
                'api_authenticated' => true,
            ]);

        } catch (\Exception $e) {
            Log::error('API Auth Failed', ['error' => $e->getMessage()]);
            return $this->unauthorized($request);
        }

        return $next($request);
    }

    private function unauthorized(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'API authentication required'
            ], 401);
        }

        return response()->view('auth.api-error', [
            'error_message' => 'API authentication required.',
            'error_code' => 'NOT_AUTHENTICATED'
        ], 401);
    }
}