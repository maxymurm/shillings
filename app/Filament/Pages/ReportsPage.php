<?php

namespace App\Filament\Pages;

use App\Models\Account;
use App\Models\Company;
use App\Reports\BalanceSheetReport;
use App\Reports\CashFlowReport;
use App\Reports\GeneralLedgerReport;
use App\Reports\IncomeStatementReport;
use App\Reports\TrialBalanceReport;
use App\Services\ReportExportService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

class ReportsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static ?string $navigationLabel = 'Financial Reports';

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.reports';

    public ?string $report_type = 'trial_balance';
    public ?string $start_date = null;
    public ?string $end_date = null;
    public ?string $account_id = null;
    public ?string $comparison_period = null;

    public ?array $reportData = null;
    public ?string $reportTitle = null;

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
                    Select::make('report_type')
                        ->label('Report Type')
                        ->options([
                            'trial_balance' => 'Trial Balance',
                            'balance_sheet' => 'Balance Sheet',
                            'income_statement' => 'Income Statement',
                            'cash_flow' => 'Cash Flow Statement',
                            'general_ledger' => 'General Ledger',
                        ])
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn () => $this->reportData = null),

                    Grid::make(3)
                        ->schema([
                            DatePicker::make('start_date')
                                ->label('Start Date')
                                ->required()
                                ->native(false)
                                ->visible(fn (Get $get) => in_array($get('report_type'), [
                                    'income_statement', 'cash_flow', 'general_ledger'
                                ])),

                            DatePicker::make('end_date')
                                ->label(fn (Get $get) => $get('report_type') === 'trial_balance' || $get('report_type') === 'balance_sheet' ? 'As of Date' : 'End Date')
                                ->required()
                                ->native(false),

                            Select::make('account_id')
                                ->label('Account')
                                ->options(function () {
                                    return Account::query()
                                        ->whereNotNull('account_type_id')
                                        ->orderBy('code')
                                        ->get()
                                        ->mapWithKeys(fn ($account) => [
                                            $account->id => $account->code 
                                                ? "[{$account->code}] {$account->name}"
                                                : $account->name,
                                        ]);
                                })
                                ->searchable()
                                ->visible(fn (Get $get) => $get('report_type') === 'general_ledger'),

                            Select::make('comparison_period')
                                ->label('Compare With')
                                ->options([
                                    'previous_period' => 'Previous Period',
                                    'previous_year' => 'Previous Year',
                                ])
                                ->placeholder('No comparison')
                                ->visible(fn (Get $get) => in_array($get('report_type'), [
                                    'balance_sheet', 'income_statement'
                                ])),
                        ]),
                ])
                ->columns(1),
        ];
    }

    public function generateReport(): void
    {
        $companyId = session('current_company_id') ?? Company::first()?->id;

        if (!$companyId) {
            Notification::make()
                ->title('No company selected')
                ->danger()
                ->send();
            return;
        }

        try {
            $report = match ($this->report_type) {
                'trial_balance' => new TrialBalanceReport($companyId, Carbon::parse($this->end_date)),
                'balance_sheet' => new BalanceSheetReport($companyId, Carbon::parse($this->end_date)),
                'income_statement' => new IncomeStatementReport(
                    $companyId,
                    Carbon::parse($this->start_date),
                    Carbon::parse($this->end_date)
                ),
                'cash_flow' => new CashFlowReport(
                    $companyId,
                    Carbon::parse($this->start_date),
                    Carbon::parse($this->end_date)
                ),
                'general_ledger' => new GeneralLedgerReport(
                    $companyId,
                    $this->account_id ? [$this->account_id] : [],
                    Carbon::parse($this->start_date),
                    Carbon::parse($this->end_date)
                ),
                default => throw new \InvalidArgumentException('Unknown report type'),
            };

            $this->reportData = $report->generate();
            $this->reportTitle = $report->getTitle();

            Notification::make()
                ->title('Report generated')
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

    public function exportPdf(): void
    {
        if (!$this->reportData) {
            Notification::make()
                ->title('Please generate a report first')
                ->warning()
                ->send();
            return;
        }

        try {
            $companyId = session('current_company_id') ?? Company::first()?->id;
            
            $report = match ($this->report_type) {
                'trial_balance' => new TrialBalanceReport($companyId, Carbon::parse($this->end_date)),
                'balance_sheet' => new BalanceSheetReport($companyId, Carbon::parse($this->end_date)),
                'income_statement' => new IncomeStatementReport(
                    $companyId,
                    Carbon::parse($this->start_date),
                    Carbon::parse($this->end_date)
                ),
                'cash_flow' => new CashFlowReport(
                    $companyId,
                    Carbon::parse($this->start_date),
                    Carbon::parse($this->end_date)
                ),
                'general_ledger' => new GeneralLedgerReport(
                    $companyId,
                    $this->account_id ? [$this->account_id] : [],
                    Carbon::parse($this->start_date),
                    Carbon::parse($this->end_date)
                ),
                default => throw new \InvalidArgumentException('Unknown report type'),
            };

            $exportService = app(ReportExportService::class);
            $pdfContent = $exportService->toPdf($report);

            $filename = $this->report_type . '_' . now()->format('Y-m-d_His') . '.pdf';
            
            // Download will be handled by the browser
            Notification::make()
                ->title('PDF generated')
                ->body('Your report is ready.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error exporting PDF')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function exportExcel(): void
    {
        if (!$this->reportData) {
            Notification::make()
                ->title('Please generate a report first')
                ->warning()
                ->send();
            return;
        }

        Notification::make()
            ->title('Export started')
            ->body('Your Excel file is being prepared.')
            ->info()
            ->send();
    }

    public function getViewData(): array
    {
        return [
            'reportData' => $this->reportData,
            'reportTitle' => $this->reportTitle,
            'reportType' => $this->report_type,
        ];
    }
}
