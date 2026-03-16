<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirects authenticated users to the company onboarding page
 * if they have no companies and aren't already on the onboarding page.
 *
 * Also auto-sets active_company_id if a user has companies but
 * none is currently selected in the session.
 */
class EnsureCompanyExists
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return $next($request);
        }

        $onboardingPath = '/admin/company-onboarding';

        // Don't intercept the onboarding page itself, logout, or livewire internals
        if (
            $request->is('admin/company-onboarding*') ||
            $request->is('admin/logout*') ||
            $request->is('livewire/*')
        ) {
            return $next($request);
        }

        // Check if user has any companies
        $userCompanyCount = auth()->user()->companies()->count();

        if ($userCompanyCount === 0) {
            return redirect($onboardingPath);
        }

        // Auto-set active company if none selected (or session points to a company the user no longer owns)
        $activeId = session('active_company_id');
        $validActive = $activeId
            ? auth()->user()->companies()->where('companies.id', $activeId)->exists()
            : false;

        if (! $validActive) {
            $firstCompany = auth()->user()->companies()->first();
            if ($firstCompany) {
                session(['active_company_id' => $firstCompany->id]);
            }
        }

        return $next($request);
    }
}
