<?php

use App\Models\Company;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/admin/switch-organisation/{company}', function (Company $company) {
    $user = auth()->user();
    // Auto-attach user as member if they aren't yet (covers seeded/imported orgs)
    if (! $user->companies()->where('companies.id', $company->id)->exists()) {
        $user->companies()->attach($company->id, ['role' => 'member']);
    }
    session(['active_company_id' => $company->id]);
    return redirect()->intended(route('filament.admin.pages.dashboard'));
})->middleware(['web', 'auth'])->name('switch-organisation');
