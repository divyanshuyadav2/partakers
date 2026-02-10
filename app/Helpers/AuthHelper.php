<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class AuthHelper
{
    public static function getAuthenticatedUser()
    {
        $userUIN = session('authenticated_user_uin');

        if (!$userUIN || !session('api_authenticated')) {
            return null;
        }

        return DB::table('admn_user_logi_mast')
            ->where('User_UIN', $userUIN)
            ->select('User_UIN', 'User_Name', 'Prmy_Emai', 'User_Logo')
            ->first();
    }

    public static function isAuthenticated(): bool
    {
        return session('api_authenticated', false) && session('authenticated_user_uin');
    }

    public static function getUserOrganizations()
    {
        $userUIN = session('authenticated_user_uin');

        if (!$userUIN) {
            return collect();
        }

        return DB::table('admn_user_orga_rela')
            ->join('admn_orga_mast', 'admn_user_orga_rela.Orga_UIN', '=', 'admn_orga_mast.Orga_UIN')
            ->where('admn_user_orga_rela.User_UIN', $userUIN)  // Use User_UIN from user_orga_rela
            ->where('admn_user_orga_rela.Stau_UIN', 100201)  // Only active associations
            ->select('admn_orga_mast.Orga_UIN', 'admn_orga_mast.Orga_Name as name')
            ->distinct()
            ->get();
    }

    public static function getSelectedOrganization()
    {
        $orgaUIN = session('selected_Orga_UIN');

        if (!$orgaUIN) {
            return null;
        }

        return DB::table('admn_orga_mast')
            ->where('Orga_UIN', $orgaUIN)
            ->select('Orga_UIN', 'Orga_Name as name')
            ->first();
    }

    public static function getSelectedOrganizationName(): string
    {
        $org = self::getSelectedOrganization();
        return $org ? $org->name : 'No Organization Selected';
    }
}
