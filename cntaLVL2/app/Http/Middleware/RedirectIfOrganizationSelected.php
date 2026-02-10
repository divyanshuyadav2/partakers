<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfOrganizationSelected
{
    /**
     * Redirect to contacts if organization is already selected
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if organization is already selected
        if (session()->has('selected_Orga_UIN')) {
            return redirect()->route('contacts.index');
        }
        
        return $next($request);
    }
}