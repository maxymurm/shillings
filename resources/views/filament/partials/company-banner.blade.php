@auth
@php
    $activeCompanyId = session('active_company_id');
    $activeOrg       = $activeCompanyId ? \App\Models\Company::find($activeCompanyId) : null;
    $totalOrgs       = \App\Models\Company::count();
@endphp

@if(! $activeOrg && $totalOrgs === 0)
{{-- No org at all — strong nudge --}}
<div class="mb-5 rounded-xl border border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/30 px-5 py-4">
    <div class="flex items-start gap-3">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width:22px;height:22px;color:#f59e0b;flex-shrink:0;margin-top:2px">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
        </svg>
        <div class="flex-1">
            <h3 class="font-semibold text-amber-800 dark:text-amber-200">No organisation set up yet</h3>
            <p class="text-sm text-amber-700 dark:text-amber-300 mt-0.5">
                An organisation is required before you can manage accounts, transactions, or reports.
            </p>
        </div>
        <a
            href="{{ url('/admin/company-onboarding') }}"
            style="white-space:nowrap"
            class="inline-flex items-center gap-1.5 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors"
        >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width:16px;height:16px">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Create Organisation
        </a>
    </div>
</div>
@endif

@endauth
