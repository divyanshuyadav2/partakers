<?php

use App\Http\Controllers\Api\RedirectAuthController;
use App\Livewire\Contacts\Create as ContactsCreate;
use App\Livewire\Contacts\CreateByLink;
use App\Livewire\Contacts\Edit as ContactsEdit;
use App\Livewire\Contacts\Index as ContactsIndex;
use App\Livewire\Contacts\Show as ContactsShow;
use App\Livewire\Organizations\Select;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/api/auth/redirect', [RedirectAuthController::class, 'handleRedirect'])
    ->name('api.auth.redirect');

Route::get('/contact/create-by-link/{token}', CreateByLink::class)
    ->name('contact.create-by-link');

// API authenticated routes
Route::middleware(['api.authenticated'])->group(function () {
    // Organization routes
    Route::get('/select-organization', Select::class)
        ->middleware('organization.selected')
        ->name('organization.select');

    Route::post('/switch-organization', function (Request $request) {
        $request->validate(['orga_uin' => 'required|numeric']);

        $userUIN = session('authenticated_user_uin');

        $hasAccess = DB::table('admn_user_orga_rela')
            ->where('User_UIN', $userUIN)
            ->where('Orga_UIN', $request->orga_uin)
            ->where('Stau_UIN', 100201)
            ->exists();

        if (!$hasAccess) {
            return redirect()->back()->with('error', 'Access denied to selected organization');
        }

        session(['selected_Orga_UIN' => $request->orga_uin]);
        return redirect()->back()->with('success', 'Organization switched successfully');
    })->name('organization.switch');

    Route::post('/api/auth/logout', function () {
        session()->flush();
        session()->regenerate();
        return redirect('/')->with('success', 'Logged out successfully');
    })->name('api.auth.logout');

    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->json(['status' => 'success', 'message' => 'Logged out successfully.']);
    })->name('logout');

    // Contact routes with organization context
    Route::middleware(['organization.context'])->group(function () {
        // ✅ MODIFIED: Added contact.owner middleware
        Route::get('/', ContactsIndex::class)->name('home');
        Route::get('/contacts', ContactsIndex::class)->name('contacts.index');
        Route::get('/contacts/create', ContactsCreate::class)->name('contacts.create');

        // ✅ NEW: Added security middleware
        Route::get('/contacts/{contact}/edit', ContactsEdit::class)
            ->name('contacts.edit')
            ->middleware('contact.owner')
            ->where('contact', '\d+');

        Route::get('/contacts/{contact}', ContactsShow::class)
            ->name('contacts.show')
            ->middleware('contact.owner')
            ->where('contact', '\d+');

        // Document routes
        Route::get('/download-document/{path}', function ($path) {
            try {
                $filePath = base64_decode($path, true);

                if (!$filePath) {
                    \Log::error('Invalid base64 encoding for document path');
                    abort(400, 'Invalid file path');
                }

                if (!Storage::disk('public')->exists($filePath)) {
                    \Log::error('Document file not found', ['filePath' => $filePath]);
                    abort(404, 'File not found');
                }

                $fullPath = Storage::disk('public')->path($filePath);
                $mimeType = mime_content_type($fullPath);

                return response()->download($fullPath, basename($fullPath), [
                    'Content-Type' => $mimeType
                ]);
            } catch (\Exception $e) {
                \Log::error('Error downloading document: ' . $e->getMessage());
                abort(500, 'Error downloading file');
            }
        })->name('download.document');

        Route::get('/download-doc/{encodedPath}', function ($encodedPath) {
            $filePath = base64_decode($encodedPath, true);

            if (!$filePath || !Storage::disk('public')->exists($filePath)) {
                abort(404, 'File not found');
            }

            $fullPath = Storage::disk('public')->path($filePath);

            if (!file_exists($fullPath)) {
                abort(404, 'File not found on disk');
            }

            return response()->download($fullPath, basename($filePath));
        })->name('download.doc');
        Route::get('/contacts/export/csv', [App\Http\Controllers\ContactExportController::class, 'exportCsv'])
        ->name('contacts.export.csv');
    });
});

// Debug routes (development only)
if (app()->isLocal()) {
    Route::get('/debug-documents', function () {
        $documents = \App\Models\Admn_Docu_Mast::where('Stau', 100201)
            ->limit(5)
            ->get();

        return response()->json([
            'documents' => $documents->map(fn($doc) => [
                'Admn_Docu_Mast_UIN' => $doc->Admn_Docu_Mast_UIN,
                'Docu_Atch_Path' => $doc->Docu_Atch_Path,
                'exists' => Storage::disk('public')->exists($doc->Docu_Atch_Path),
            ]),
        ]);
    })->middleware(['api.authenticated']);

    Route::get('/debug-orga-relation', function () {
        $userUIN = session('authenticated_user_uin');
        $relations = DB::table('admn_user_orga_rela')
            ->where('User_UIN', $userUIN)
            ->get();

        return response()->json([
            'authenticated_user_uin' => $userUIN,
            'relations' => $relations,
            'count' => $relations->count(),
        ]);
    })->middleware(['api.authenticated']);
}

require __DIR__ . '/auth.php';