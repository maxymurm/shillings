<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Company;
use App\Queries\AccountRegisterQuery;
use App\Reports\BalanceSheetReport;
use App\Reports\CashFlowReport;
use App\Reports\GeneralLedgerReport;
use App\Reports\IncomeStatementReport;
use App\Reports\TrialBalanceReport;
use App\Services\ReportExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function __construct(
        protected ReportExportService $exportService
    ) {}

    /**
     * List available reports.
     *
     * @group Reports
     */
    public function index(): JsonResponse
    {
        $reports = [
            [
                'id' => 'trial-balance',
                'name' => 'Trial Balance',
                'description' => 'Lists all accounts with debit and credit columns.',
                'parameters' => ['company_id', 'as_of_date'],
            ],
            [
                'id' => 'balance-sheet',
                'name' => 'Balance Sheet',
                'description' => 'Statement of Financial Position: Assets = Liabilities + Equity.',
                'parameters' => ['company_id', 'as_of_date'],
            ],
            [
                'id' => 'income-statement',
                'name' => 'Income Statement',
                'description' => 'Profit & Loss: Revenue - Expenses = Net Income.',
                'parameters' => ['company_id', 'start_date', 'end_date'],
            ],
            [
                'id' => 'cash-flow',
                'name' => 'Cash Flow Statement',
                'description' => 'Cash flows from Operating, Investing, and Financing activities.',
                'parameters' => ['company_id', 'start_date', 'end_date'],
            ],
            [
                'id' => 'general-ledger',
                'name' => 'General Ledger',
                'description' => 'Complete listing of all accounts and transactions.',
                'parameters' => ['company_id', 'start_date', 'end_date', 'account_types', 'account_ids'],
            ],
            [
                'id' => 'account-register',
                'name' => 'Account Register',
                'description' => 'Transaction register for a specific account with running balance.',
                'parameters' => ['account_id', 'start_date', 'end_date', 'search'],
            ],
        ];

        return response()->json([
            'data' => $reports,
        ]);
    }

    /**
     * Generate Trial Balance report.
     *
     * @group Reports
     */
    public function trialBalance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|uuid|exists:companies,id',
            'as_of_date' => 'nullable|date',
        ]);

        $company = Company::findOrFail($validated['company_id']);
        $asOfDate = isset($validated['as_of_date'])
            ? Carbon::parse($validated['as_of_date'])
            : now();

        $report = new TrialBalanceReport;
        $report->forCompany($company)->forPeriod(null, $asOfDate);
        $data = $report->generate();

        return response()->json([
            'data' => $report->toArray(),
        ]);
    }

    /**
     * Generate Balance Sheet report.
     *
     * @group Reports
     */
    public function balanceSheet(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|uuid|exists:companies,id',
            'as_of_date' => 'nullable|date',
        ]);

        $company = Company::findOrFail($validated['company_id']);
        $asOfDate = isset($validated['as_of_date'])
            ? Carbon::parse($validated['as_of_date'])
            : now();

        $report = new BalanceSheetReport;
        $report->forCompany($company)->forPeriod(null, $asOfDate);
        $data = $report->generate();

        return response()->json([
            'data' => $report->toArray(),
        ]);
    }

    /**
     * Generate Income Statement report.
     *
     * @group Reports
     */
    public function incomeStatement(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|uuid|exists:companies,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $company = Company::findOrFail($validated['company_id']);
        $startDate = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])
            : now()->startOfYear();
        $endDate = isset($validated['end_date'])
            ? Carbon::parse($validated['end_date'])
            : now();

        $report = new IncomeStatementReport;
        $report->forCompany($company)->forPeriod($startDate, $endDate);
        $data = $report->generate();

        return response()->json([
            'data' => $report->toArray(),
        ]);
    }

    /**
     * Generate Cash Flow Statement report.
     *
     * @group Reports
     */
    public function cashFlow(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|uuid|exists:companies,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $company = Company::findOrFail($validated['company_id']);
        $startDate = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])
            : now()->startOfYear();
        $endDate = isset($validated['end_date'])
            ? Carbon::parse($validated['end_date'])
            : now();

        $report = new CashFlowReport;
        $report->forCompany($company)->forPeriod($startDate, $endDate);
        $data = $report->generate();

        return response()->json([
            'data' => $report->toArray(),
        ]);
    }

    /**
     * Generate General Ledger report.
     *
     * @group Reports
     */
    public function generalLedger(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|uuid|exists:companies,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'account_types' => 'nullable|array',
            'account_types.*' => 'string|in:Asset,Liability,Equity,Income,Expense',
            'account_ids' => 'nullable|array',
            'account_ids.*' => 'uuid|exists:accounts,id',
        ]);

        $company = Company::findOrFail($validated['company_id']);
        $startDate = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])
            : now()->startOfYear();
        $endDate = isset($validated['end_date'])
            ? Carbon::parse($validated['end_date'])
            : now();

        $report = new GeneralLedgerReport;
        $report->forCompany($company)->forPeriod($startDate, $endDate);

        if (! empty($validated['account_types'])) {
            $report->forAccountTypes($validated['account_types']);
        }

        if (! empty($validated['account_ids'])) {
            $report->forAccounts($validated['account_ids']);
        }

        $data = $report->generate();

        return response()->json([
            'data' => $report->toArray(),
        ]);
    }

    /**
     * Get account register (transaction list with running balance).
     *
     * @group Reports
     */
    public function accountRegister(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => 'required|uuid|exists:accounts,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'search' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:10|max:100',
            'page' => 'nullable|integer|min:1',
            'include_voided' => 'nullable|boolean',
            'sort' => 'nullable|string|in:asc,desc',
        ]);

        $account = Account::findOrFail($validated['account_id']);

        $query = AccountRegisterQuery::for($account);

        if (isset($validated['start_date']) || isset($validated['end_date'])) {
            $query->between(
                isset($validated['start_date']) ? Carbon::parse($validated['start_date']) : null,
                isset($validated['end_date']) ? Carbon::parse($validated['end_date']) : null
            );
        }

        if (isset($validated['search'])) {
            $query->search($validated['search']);
        }

        if (isset($validated['per_page'])) {
            $query->perPage($validated['per_page']);
        }

        if (isset($validated['include_voided']) && $validated['include_voided']) {
            $query->includeVoided();
        }

        if (isset($validated['sort'])) {
            $query->sortDirection($validated['sort']);
        }

        $paginated = $query->paginate($validated['page'] ?? null);

        return response()->json([
            'data' => $paginated->items(),
            'metadata' => $query->getMetadata(),
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * Export a report to PDF, Excel, or CSV.
     *
     * @group Reports
     */
    public function export(Request $request): BinaryFileResponse|JsonResponse
    {
        $validated = $request->validate([
            'report' => 'required|string|in:trial-balance,balance-sheet,income-statement,cash-flow,general-ledger',
            'format' => 'required|string|in:pdf,excel,csv',
            'company_id' => 'required|uuid|exists:companies,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'as_of_date' => 'nullable|date',
            'account_types' => 'nullable|array',
            'account_ids' => 'nullable|array',
        ]);

        $company = Company::findOrFail($validated['company_id']);

        // Determine dates
        $startDate = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])
            : now()->startOfYear();
        $endDate = isset($validated['end_date'])
            ? Carbon::parse($validated['end_date'])
            : (isset($validated['as_of_date']) ? Carbon::parse($validated['as_of_date']) : now());

        // Create the appropriate report
        $report = match ($validated['report']) {
            'trial-balance' => (new TrialBalanceReport)->forCompany($company)->forPeriod(null, $endDate),
            'balance-sheet' => (new BalanceSheetReport)->forCompany($company)->forPeriod(null, $endDate),
            'income-statement' => (new IncomeStatementReport)->forCompany($company)->forPeriod($startDate, $endDate),
            'cash-flow' => (new CashFlowReport)->forCompany($company)->forPeriod($startDate, $endDate),
            'general-ledger' => (new GeneralLedgerReport)->forCompany($company)->forPeriod($startDate, $endDate),
        };

        // Apply optional filters for General Ledger
        if ($validated['report'] === 'general-ledger' && $report instanceof GeneralLedgerReport) {
            if (! empty($validated['account_types'])) {
                $report->forAccountTypes($validated['account_types']);
            }
            if (! empty($validated['account_ids'])) {
                $report->forAccounts($validated['account_ids']);
            }
        }

        // Generate the report
        $report->generate();

        if (! $report->isValid()) {
            return response()->json([
                'message' => 'Report generation failed',
                'errors' => $report->getErrors(),
            ], 422);
        }

        // Export
        try {
            $filePath = $this->exportService->export($report, $validated['format']);

            $mimeType = match ($validated['format']) {
                'pdf' => 'application/pdf',
                'excel' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'csv' => 'text/csv',
            };

            return response()->download($filePath, basename($filePath), [
                'Content-Type' => $mimeType,
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Export failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
