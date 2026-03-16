<?php

namespace App\Filament\Pages;

use App\Models\Company;
use App\Models\Currency;
use App\Services\ChartOfAccountsService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class CompanyOnboardingPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.company-onboarding';

    protected static string|\BackedEnum|null $navigationIcon = null;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Welcome to Shillings — Set up your Organisation';

    protected static ?string $slug = 'company-onboarding';

    // Form state
    public string $companyName = '';
    public ?string $currencyId = null;
    public string $template = '';

    public function mount(): void
    {
        // If user already has a company, send them to the dashboard
        $hasCompany = Company::exists();
        if ($hasCompany && session('active_company_id')) {
            $this->redirect(route('filament.admin.pages.dashboard'));
            return;
        }

        // Pre-select a sensible currency default
        $kes = Currency::where('code', 'KES')->first();
        $usd = Currency::where('code', 'USD')->first();
        $this->currencyId = $kes?->id ?? $usd?->id ?? Currency::first()?->id;
    }

    public function create(): void
    {
        $this->validate([
            'companyName' => 'required|string|min:2|max:255',
            'currencyId'  => 'required|exists:currencies,id',
        ], [
            'companyName.required' => 'Give your organisation a name.',
            'currencyId.required'  => 'Select a default currency.',
        ]);

        $company = Company::create([
            'name'                => $this->companyName,
            'default_currency_id' => $this->currencyId,
            'is_active'           => true,
        ]);

        // Attach the current user as owner
        $company->users()->attach(auth()->id(), ['role' => 'owner']);

        // Set as active company
        session(['active_company_id' => $company->id]);

        // Import a starter chart of accounts if a template was chosen
        if ($this->template !== '' && $this->template !== 'none') {
            try {
                $result = app(ChartOfAccountsService::class)
                    ->importTemplate($company, $this->template);

                Notification::make()
                    ->title('Organisation created!')
                    ->body("Imported {$result['accounts_created']} accounts from the {$result['template']} template.")
                    ->success()
                    ->send();
            } catch (\Throwable $e) {
                // Company was still created — template is optional
                Notification::make()
                    ->title('Organisation created')
                    ->body('Organisation ready, but the starter template could not be loaded. You can import accounts manually.')
                    ->warning()
                    ->send();
            }
        } else {
            Notification::make()
                ->title('Organisation created!')
                ->body('You can now start adding accounts and recording transactions.')
                ->success()
                ->send();
        }

        $this->redirect(route('filament.admin.pages.dashboard'));
    }

    /**
     * Get available currencies for the selector.
     */
    public function getCurrencies(): \Illuminate\Support\Collection
    {
        return Currency::orderBy('code')->get();
    }

    /**
     * Get available chart-of-accounts templates.
     */
    public function getTemplates(): array
    {
        try {
            return app(ChartOfAccountsService::class)->getAvailableTemplates();
        } catch (\Throwable) {
            return [];
        }
    }
}
