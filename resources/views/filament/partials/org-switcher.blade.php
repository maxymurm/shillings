@auth
@php
    $activeCompanyId = session('active_company_id');
    $activeOrg       = $activeCompanyId ? \App\Models\Company::find($activeCompanyId) : null;
    // Only show orgs this user belongs to, excluding the active one
    $otherOrgs       = auth()->user()->companies()
                           ->when($activeCompanyId, fn($q) => $q->where('companies.id', '!=', $activeCompanyId))
                           ->orderBy('name')->get();

    // Compute initials: "Hessel, Jakubowski and Mitchell" → "HJM"
    $orgAbbr = '';
    if ($activeOrg) {
        $skip    = ['and','or','the','of','a','an','de','van','by'];
        $cleaned = preg_replace('/\b(Inc|LLC|Ltd|PLC|Corp|Co\.?|Group|Holdings|Pty)\b\.?/i', '', $activeOrg->name);
        $words   = preg_split('/[\s,\-&]+/', $cleaned, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($words as $w) {
            if (strlen($w) > 1 && !in_array(strtolower($w), $skip)) {
                $orgAbbr .= strtoupper($w[0]);
            }
        }
        if (strlen($orgAbbr) < 2) {
            $orgAbbr = strtoupper(substr(trim($cleaned), 0, 3));
        }
        $orgAbbr = substr($orgAbbr, 0, 4);
    }
@endphp

@if($activeOrg)
<div x-data="{ open: false }" @click.outside="open = false" style="position:relative;display:inline-flex;align-items:center">
    {{-- Single green pill: building icon + abbr + chevron --}}
    <button
        @click="open = !open"
        type="button"
        style="display:inline-flex;align-items:center;gap:5px;padding:5px 11px;font-size:12px;font-weight:700;letter-spacing:.05em;color:#fff;background:#10b981;border:none;border-radius:8px;cursor:pointer;white-space:nowrap;line-height:1;transition:background .15s"
        onmouseover="this.style.background='#059669'"
        onmouseout="this.style.background='#10b981'"
    >
        {{-- Building icon --}}
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width:13px;height:13px;flex-shrink:0;opacity:.85">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 0h.008v.008h-.008v-.008z"/>
        </svg>
        {{ $orgAbbr }}
        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            style="width:10px;height:10px;flex-shrink:0;transition:transform .15s"
            :style="open ? 'transform:rotate(180deg)' : ''"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    {{-- Dropdown --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        x-cloak
        style="position:absolute;top:calc(100% + 6px);right:0;min-width:240px;z-index:9999;border-radius:10px;overflow:hidden;box-shadow:0 12px 28px rgba(0,0,0,.35);border:1px solid rgba(255,255,255,.08);background:#1e293b"
    >
        {{-- Current org (highlighted) --}}
        <div style="padding:8px 12px;display:flex;align-items:center;gap:8px;background:rgba(16,185,129,.15);border-bottom:1px solid rgba(255,255,255,.07)">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width:14px;height:14px;flex-shrink:0;color:#10b981">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span style="font-size:13px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#6ee7b7">{{ $activeOrg->name }}</span>
        </div>

        @if($otherOrgs->isNotEmpty())
        <div style="padding:4px 0">
            <div style="padding:4px 12px;font-size:10px;text-transform:uppercase;letter-spacing:.05em;color:#64748b">Switch to</div>
            @foreach($otherOrgs as $org)
            <a
                href="{{ url('/admin/switch-organisation/' . $org->id) }}"
                style="display:flex;align-items:center;gap:8px;padding:8px 12px;font-size:13px;text-decoration:none;transition:background .1s;color:#cbd5e1"
                onmouseover="this.style.background='rgba(255,255,255,.06)'"
                onmouseout="this.style.background='transparent'"
            >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width:14px;height:14px;flex-shrink:0;opacity:.4">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 0h.008v.008h-.008v-.008z"/>
                </svg>
                <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $org->name }}</span>
            </a>
            @endforeach
        </div>
        <div style="height:1px;background:rgba(255,255,255,.07)"></div>
        @endif

        {{-- New org link --}}
        <a
            href="{{ url('/admin/company-onboarding') }}"
            style="display:flex;align-items:center;gap:8px;padding:9px 12px;font-size:13px;font-weight:500;text-decoration:none;transition:background .1s;color:#10b981"
            onmouseover="this.style.background='rgba(255,255,255,.06)'"
            onmouseout="this.style.background='transparent'"
        >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width:14px;height:14px;flex-shrink:0">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            New Organisation
        </a>
    </div>
</div>
@endif
@endauth
