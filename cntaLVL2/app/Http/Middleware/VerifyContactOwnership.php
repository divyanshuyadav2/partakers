<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Closure;

class VerifyContactOwnership
{
    public function handle(Request $request, Closure $next)
    {
        $contact = $request->route('contact');
        $orgaUIN = session('selected_Orga_UIN');

        if (!$contact) {
            return $next($request);
        }

        // Verify contact belongs to user's organization
        $belongsToOrg = DB::table('admn_user_mast')
            ->where('Admn_User_Mast_UIN', $contact->Admn_User_Mast_UIN ?? $contact)
            ->where('Admn_Orga_Mast_UIN', $orgaUIN)
            ->exists();

        if (!$belongsToOrg) {
            abort(403, 'Unauthorized to access this contact');
        }

        return $next($request);
    }
}
