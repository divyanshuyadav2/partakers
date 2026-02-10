<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login with only User_UIN
     */
    public function login(Request $request)
    {
        $request->validate([
            'User_UIN' => 'required|numeric',
        ]);

        // Find user by User_UIN
        $user = User::where('User_UIN', $request->User_UIN)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'User_UIN' => ['User UIN not found in database.'],
            ]);
        }

        // Get user's organizations with names from admn_orga_mast
        $organizations = DB::table('admn_user_orga_rela')
            ->join('admn_orga_mast', 'admn_user_orga_rela.Orga_UIN', '=', 'admn_orga_mast.Orga_UIN')
            ->where('admn_user_orga_rela.User_UIN', $request->User_UIN)
            ->select('admn_orga_mast.Orga_UIN', 'admn_orga_mast.Orga_Name')
            ->get();

        // Delete existing tokens for this user
        $user->tokens()->delete();

        // Create new Sanctum token
        $token = $user->createToken('api-access-token', ['*'])->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'User_UIN' => $user->User_UIN,
                    'User_Name' => $user->User_Name,
                    'Prmy_Emai' => $user->Prmy_Emai,
                    'User_Logo' => $user->User_Logo,
                ],
                'organizations' => $organizations,
                'token' => $token,
                'token_type' => 'Bearer'
            ],
            'message' => 'Login successful'
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Get current authenticated user
     */
    public function me(Request $request)
    {
        $user = $request->user();

        // Get user's organizations
        $organizations = DB::table('admn_user_orga_rela')
            ->join('admn_orga_mast', 'admn_user_orga_rela.Orga_UIN', '=', 'admn_orga_mast.Orga_UIN')
            ->where('admn_user_orga_rela.User_UIN', $user->User_UIN)
            ->select('admn_orga_mast.Orga_UIN', 'admn_orga_mast.Orga_Name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'User_UIN' => $user->User_UIN,
                    'User_Name' => $user->User_Name,
                    'Prmy_Emai' => $user->Prmy_Emai,
                    'User_Logo' => $user->User_Logo,
                ],
                'organizations' => $organizations,
                'current_organization' => session('selected_Orga_UIN')
            ]
        ]);
    }
}
