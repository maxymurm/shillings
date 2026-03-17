<?php

namespace App\Filament\Pages;

use App\Models\Company;
use App\Reports\TaxSummaryReport;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class TaxSummaryReportPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static ?string $navigationLabel = 'Tax Summary';

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.tax-summary-report';

    public ?string $start_date = null;
    public ?string $end_date = null;

    public ?array $reportData = null;

    public function mount(): void
    {
        $this->start_date = now()->startOfMonth()->format('Y-m-d');
        $this->end_date = now()->endOfMonth()->format('Y-m-d');
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Report Parameters')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            DatePicker::make('start_date')
                                ->label('Start Date')
                                ->required()
                                ->native(false),

                            DatePicker::make('end_date')
                                ->label('End Date')
                                ->required()
                                ->native(false),
                        ]),
                ])
                ->columns(1),
        ];
    }

    public function generateReport(): void
    {
        $companyId = session('current_company_id') ?? Company::first()?->id;

        if (! $companyId) {
            Notification::make()
                ->title('No company selected')
                ->danger()
                ->send();

            return;
        }

        try {
            $company = Company::findOrFail($companyId);

            $report = new TaxSummaryReport;
            $report->forCompany($company)
                ->forPeriod(Carbon::parse($this->start_date), Carbon::parse($this->end_date));

            $this->reportData = $report->generate();

            Notification::make()
                ->title('Tax Summary generated')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error generating report')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function getViewData(): array
    {
        return [
            'reportData' => $this->reportData,
        ];
    }
}
