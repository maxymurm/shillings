<x-filament-panels::page>
<div style="max-width:640px;margin:0 auto;padding:8px 0 32px">

    {{-- ── HERO ── --}}
    <div style="text-align:center;margin-bottom:28px">
        <div style="display:inline-flex;align-items:center;justify-content:center;width:56px;height:56px;border-radius:50%;background:rgba(16,185,129,.15);margin-bottom:12px">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="1.5" style="width:28px;height:28px">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
            </svg>
        </div>
        <h2 style="font-size:22px;font-weight:700;color:#f1f5f9;margin:0 0 6px">Welcome to Shillings</h2>
        <p style="font-size:14px;color:#94a3b8;margin:0 auto;max-width:420px">
            Let's get you started. Create your first organisation to begin managing your accounts and finances.
        </p>
    </div>

    {{-- ── FEATURES ── --}}
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:24px">
        <div style="text-align:center;padding:14px 12px;border-radius:10px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08)">
            <div style="font-size:20px;margin-bottom:4px">🔒</div>
            <div style="font-size:12px;font-weight:600;color:#e2e8f0;margin-bottom:3px">Private &amp; Offline</div>
            <div style="font-size:11px;color:#64748b">No sign-up needed. Your data stays on your device.</div>
        </div>
        <div style="text-align:center;padding:14px 12px;border-radius:10px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08)">
            <div style="font-size:20px;margin-bottom:4px">🏢</div>
            <div style="font-size:12px;font-weight:600;color:#e2e8f0;margin-bottom:3px">Unlimited Organisations</div>
            <div style="font-size:11px;color:#64748b">Personal finances, businesses, clients — all separate.</div>
        </div>
        <div style="text-align:center;padding:14px 12px;border-radius:10px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08)">
            <div style="font-size:20px;margin-bottom:4px">📊</div>
            <div style="font-size:12px;font-weight:600;color:#e2e8f0;margin-bottom:3px">Double-Entry</div>
            <div style="font-size:11px;color:#64748b">Professional-grade accounting with full audit trail.</div>
        </div>
    </div>

    {{-- ── FORM ── --}}
    <div style="border-radius:12px;border:1px solid rgba(255,255,255,.1);overflow:hidden">
        <div style="padding:14px 20px;background:rgba(255,255,255,.04);border-bottom:1px solid rgba(255,255,255,.07)">
            <h3 style="font-size:14px;font-weight:600;color:#f1f5f9;margin:0 0 2px">Create your first organisation</h3>
            <p style="font-size:11px;color:#64748b;margin:0">You can always add more organisations later under Settings → Organisations.</p>
        </div>

        <div style="padding:20px;display:flex;flex-direction:column;gap:16px">
            {{-- Org Name --}}
            <div>
                <label for="companyName" style="display:block;font-size:13px;font-weight:500;color:#cbd5e1;margin-bottom:5px">
                    Organisation Name <span style="color:#f87171">*</span>
                </label>
                <input
                    id="companyName"
                    type="text"
                    wire:model="companyName"
                    placeholder="e.g. My Business, Personal Finances, Client XYZ"
                    autocomplete="organization"
                    style="width:100%;box-sizing:border-box;border-radius:8px;border:1px solid rgba(255,255,255,.15);background:rgba(255,255,255,.05);color:#f1f5f9;padding:9px 14px;font-size:13px;outline:none"
                />
                @error('companyName')
                    <p style="margin:4px 0 0;font-size:11px;color:#f87171">{{ $message }}</p>
                @enderror
            </div>

            {{-- Currency --}}
            <div>
                <label for="currencyId" style="display:block;font-size:13px;font-weight:500;color:#cbd5e1;margin-bottom:5px">
                    Default Currency <span style="color:#f87171">*</span>
                </label>
                <select
                    id="currencyId"
                    wire:model="currencyId"
                    style="width:100%;box-sizing:border-box;border-radius:8px;border:1px solid rgba(255,255,255,.15);background:#1e293b;color:#f1f5f9;padding:9px 14px;font-size:13px;outline:none"
                >
                    <option value="">— Select currency —</option>
                    @foreach($this->getCurrencies() as $c)
                        <option value="{{ $c->id }}">{{ $c->code }} — {{ $c->name }}</option>
                    @endforeach
                </select>
                @error('currencyId')
                    <p style="margin:4px 0 0;font-size:11px;color:#f87171">{{ $message }}</p>
                @enderror
                <p style="margin:4px 0 0;font-size:11px;color:#64748b">You can work with multiple currencies later. This sets the default.</p>
            </div>

            {{-- Starter Template --}}
            @php $templates = $this->getTemplates(); @endphp
            @if(count($templates) > 0)
            <div>
                <label for="template" style="display:block;font-size:13px;font-weight:500;color:#cbd5e1;margin-bottom:5px">
                    Starter Chart of Accounts <span style="font-size:11px;font-weight:400;color:#64748b">(optional)</span>
                </label>
                <select
                    id="template"
                    wire:model="template"
                    style="width:100%;box-sizing:border-box;border-radius:8px;border:1px solid rgba(255,255,255,.15);background:#1e293b;color:#f1f5f9;padding:9px 14px;font-size:13px;outline:none"
                >
                    <option value="none">No template — I'll set up accounts myself</option>
                    @foreach($templates as $t)
                        <option value="{{ $t['key'] }}">{{ $t['name'] }} — {{ $t['description'] }}</option>
                    @endforeach
                </select>
                <p style="margin:4px 0 0;font-size:11px;color:#64748b">Pre-populates your chart of accounts. You can also import from GnuCash later.</p>
            </div>
            @endif
        </div>

        {{-- Actions --}}
        <div style="padding:14px 20px;background:rgba(255,255,255,.04);border-top:1px solid rgba(255,255,255,.07);display:flex;justify-content:flex-end">
            <button
                wire:click="create"
                wire:loading.attr="disabled"
                style="display:inline-flex;align-items:center;gap:7px;padding:9px 20px;background:#10b981;color:#fff;font-size:13px;font-weight:600;border:none;border-radius:8px;cursor:pointer;transition:background .15s"
                onmouseover="this.style.background='#059669'"
                onmouseout="this.style.background='#10b981'"
            >
                <span wire:loading.remove wire:target="create">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:15px;height:15px">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </span>
                <span wire:loading wire:target="create">
                    <svg style="width:15px;height:15px;animation:spin 1s linear infinite" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </span>
                Get Started
            </button>
        </div>
    </div>

    {{-- ── FOOTER NOTE ── --}}
    <p style="text-align:center;font-size:11px;color:#475569;margin-top:16px">
        You can create additional organisations at any time from <strong style="color:#64748b">Settings → Organisations</strong>.
    </p>

</div>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>
</x-filament-panels::page>