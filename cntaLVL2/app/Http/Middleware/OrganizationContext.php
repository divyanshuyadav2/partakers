<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Closure;

class OrganizationContext
{
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is API authenticated
        if (!session('api_authenticated')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'API authentication required',
                    'error_code' => 'NOT_AUTHENTICATED'
                ], 401);
            }

            return response()->view('auth.api-error', [
                'error_message' => 'API authentication required',
                'error_code' => 'NOT_AUTHENTICATED'
            ], 401);
        }

        // Get organization context
        $orgaUin = session('selected_Orga_UIN') ?? $request->header('X-Organization-UIN');

        if (!$orgaUin) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Organization context required. Please select an organization first.',
                    'error_code' => 'ORGANIZATION_REQUIRED'
                ], 422);
            }

            // Redirect to organization selection page
            return redirect()
                ->route('organization.select')
                ->with('error', 'Please select an organization first.');
        }

        // Verify user has access to this organization using User_UIN
        $userUIN = session('authenticated_user_uin');

        if (!$userUIN) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid session data',
                    'error_code' => 'INVALID_SESSION'
                ], 401);
            }

            return response()->view('auth.api-error', [
                'error_message' => 'Invalid session data. Please re-authenticate.',
                'error_code' => 'INVALID_SESSION'
            ], 401);
        }

        // Use User_UIN for access verification with active status check
        $hasAccess = DB::table('admn_user_orga_rela')
            ->where('User_UIN', $userUIN)
            ->where('Orga_UIN', $orgaUin)
            ->where('Stau_UIN', 100201)  // Only active associations
            ->exists();

        if (!$hasAccess) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied to selected organization',
                    'error_code' => 'ORGANIZATION_ACCESS_DENIED'
                ], 403);
            }

            return redirect()
                ->route('organization.select')
                ->with('error', 'Access denied to selected organization. Please select a valid organization.');
        }

        return $next($request);
    }
}
