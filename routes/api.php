<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\ChartOfAccountsController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\ExchangeRateController;
use App\Http\Controllers\Api\ImportController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ScheduledTransactionController;
use App\Http\Controllers\Api\TaxController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Shillings Accounting API
| All routes are prefixed with /api
|
*/

// Health check
Route::get('/health', fn () => response()->json(['status' => 'ok', 'app' => 'Shillings']));

// Authentication routes (public)
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
    });

    // Accounts
    Route::prefix('accounts')->group(function () {
        Route::get('/', [AccountController::class, 'index'])->middleware('ability:accounts:read');
        Route::get('/chart', [AccountController::class, 'chartOfAccounts'])->middleware('ability:accounts:read');
        Route::get('/trial-balance', [AccountController::class, 'trialBalance'])->middleware('ability:reports:read');
        Route::get('/{account}', [AccountController::class, 'show'])->middleware('ability:accounts:read');
        Route::get('/{account}/balance', [AccountController::class, 'balance'])->middleware('ability:accounts:read');
        Route::get('/{account}/running-balance', [AccountController::class, 'runningBalance'])->middleware('ability:accounts:read');
        Route::get('/{account}/transactions', [AccountController::class, 'transactions'])->middleware('ability:reports:read');
        Route::get('/{account}/transactions/export', [AccountController::class, 'exportTransactions'])->middleware('ability:reports:export');
        Route::post('/', [AccountController::class, 'store'])->middleware('ability:accounts:create');
        Route::put('/{account}', [AccountController::class, 'update'])->middleware('ability:accounts:update');
        Route::delete('/{account}', [AccountController::class, 'destroy'])->middleware('ability:accounts:delete');
    });

    // Transactions
    Route::prefix('transactions')->group(function () {
        Route::get('/', [TransactionController::class, 'index'])->middleware('ability:transactions:read');
        Route::post('/bulk', [TransactionController::class, 'bulk'])->middleware('ability:transactions:create');
        Route::get('/{transaction}', [TransactionController::class, 'show'])->middleware('ability:transactions:read');
        Route::post('/', [TransactionController::class, 'store'])->middleware('ability:transactions:create');
        Route::post('/simple', [TransactionController::class, 'storeSimple'])->middleware('ability:transactions:create');
        Route::post('/validate', [TransactionController::class, 'validate'])->middleware('ability:transactions:create');
        Route::put('/{transaction}', [TransactionController::class, 'update'])->middleware('ability:transactions:update');
        Route::delete('/{transaction}', [TransactionController::class, 'destroy'])->middleware('ability:transactions:delete');
        Route::post('/{transaction}/post', [TransactionController::class, 'post'])->middleware('ability:transactions:post');
        Route::post('/{transaction}/reverse', [TransactionController::class, 'reverse'])->middleware('ability:transactions:post');
        Route::post('/{transaction}/void', [TransactionController::class, 'void'])->middleware('ability:transactions:post');
        Route::post('/{transaction}/reconcile', [TransactionController::class, 'reconcile'])->middleware('ability:transactions:update');
        Route::post('/{transaction}/duplicate', [TransactionController::class, 'duplicate'])->middleware('ability:transactions:create');
    });

    // Exchange Rates
    Route::prefix('exchange-rates')->group(function () {
        Route::get('/', [ExchangeRateController::class, 'index'])->middleware('ability:settings:read');
        Route::get('/pair/{from}/{to}', [ExchangeRateController::class, 'getPairRate'])->middleware('ability:settings:read');
        Route::post('/convert', [ExchangeRateController::class, 'convert'])->middleware('ability:settings:read');
        Route::get('/{exchangeRate}', [ExchangeRateController::class, 'show'])->middleware('ability:settings:read');
        Route::post('/', [ExchangeRateController::class, 'store'])->middleware('ability:settings:update');
        Route::put('/{exchangeRate}', [ExchangeRateController::class, 'update'])->middleware('ability:settings:update');
        Route::delete('/{exchangeRate}', [ExchangeRateController::class, 'destroy'])->middleware('ability:settings:delete');
    });

    // Chart of Accounts Templates
    Route::prefix('chart-of-accounts')->group(function () {
        Route::get('/templates', [ChartOfAccountsController::class, 'templates'])->middleware('ability:accounts:read');
        Route::get('/templates/{key}', [ChartOfAccountsController::class, 'showTemplate'])->middleware('ability:accounts:read');
        Route::get('/preview/{key}', [ChartOfAccountsController::class, 'preview'])->middleware('ability:accounts:create');
        Route::post('/import', [ChartOfAccountsController::class, 'import'])->middleware('ability:accounts:create');
    });

    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->middleware('ability:reports:read');
        Route::get('/trial-balance', [ReportController::class, 'trialBalance'])->middleware('ability:reports:read');
        Route::get('/balance-sheet', [ReportController::class, 'balanceSheet'])->middleware('ability:reports:read');
        Route::get('/income-statement', [ReportController::class, 'incomeStatement'])->middleware('ability:reports:read');
        Route::get('/cash-flow', [ReportController::class, 'cashFlow'])->middleware('ability:reports:read');
        Route::get('/general-ledger', [ReportController::class, 'generalLedger'])->middleware('ability:reports:read');
        Route::get('/account-register', [ReportController::class, 'accountRegister'])->middleware('ability:reports:read');
        
        // Phase 13: Advanced Reports
        Route::get('/ar-aging', [ReportController::class, 'arAging'])->middleware('ability:reports:read');
        Route::get('/ap-aging', [ReportController::class, 'apAging'])->middleware('ability:reports:read');
        Route::get('/tax-summary', [ReportController::class, 'taxSummary'])->middleware('ability:reports:read');
        Route::get('/equity-statement', [ReportController::class, 'equityStatement'])->middleware('ability:reports:read');
        Route::post('/income-statement/compare', [ReportController::class, 'compareIncomeStatements'])->middleware('ability:reports:read');
        
        Route::post('/export', [ReportController::class, 'export'])->middleware('ability:reports:export');
    });

    // Contacts (Phase 9)
    Route::prefix('contacts')->group(function () {
        Route::get('/', [ContactController::class, 'index'])->middleware('ability:contacts:read');
        Route::get('/customers-with-balances', [ContactController::class, 'customersWithBalances'])->middleware('ability:contacts:read');
        Route::get('/vendors-with-balances', [ContactController::class, 'vendorsWithBalances'])->middleware('ability:contacts:read');
        Route::get('/{contact}', [ContactController::class, 'show'])->middleware('ability:contacts:read');
        Route::get('/{contact}/statement', [ContactController::class, 'statement'])->middleware('ability:contacts:read');
        Route::get('/{contact}/aging', [ContactController::class, 'aging'])->middleware('ability:contacts:read');
        Route::post('/', [ContactController::class, 'store'])->middleware('ability:contacts:create');
        Route::put('/{contact}', [ContactController::class, 'update'])->middleware('ability:contacts:update');
        Route::delete('/{contact}', [ContactController::class, 'destroy'])->middleware('ability:contacts:delete');
        Route::post('/{contact}/merge', [ContactController::class, 'merge'])->middleware('ability:contacts:update');
    });

    // Documents - Invoices, Bills, Quotes (Phase 10)
    Route::prefix('documents')->group(function () {
        Route::get('/', [DocumentController::class, 'index'])->middleware('ability:documents:read');
        Route::get('/overdue', [DocumentController::class, 'overdue'])->middleware('ability:documents:read');
        Route::get('/summary', [DocumentController::class, 'summary'])->middleware('ability:documents:read');
        Route::get('/{document}', [DocumentController::class, 'show'])->middleware('ability:documents:read');
        Route::post('/', [DocumentController::class, 'store'])->middleware('ability:documents:create');
        Route::put('/{document}', [DocumentController::class, 'update'])->middleware('ability:documents:update');
        Route::delete('/{document}', [DocumentController::class, 'destroy'])->middleware('ability:documents:delete');
        Route::post('/{document}/send', [DocumentController::class, 'send'])->middleware('ability:documents:update');
        Route::post('/{document}/payment', [DocumentController::class, 'recordPayment'])->middleware('ability:documents:update');
        Route::post('/{document}/cancel', [DocumentController::class, 'cancel'])->middleware('ability:documents:update');
        Route::post('/{document}/convert-to-invoice', [DocumentController::class, 'convertToInvoice'])->middleware('ability:documents:create');
        Route::post('/{document}/duplicate', [DocumentController::class, 'duplicate'])->middleware('ability:documents:create');
        Route::post('/{document}/credit-note', [DocumentController::class, 'createCreditNote'])->middleware('ability:documents:create');
    });

    // Budgets (Phase 11)
    Route::prefix('budgets')->group(function () {
        Route::get('/', [BudgetController::class, 'index'])->middleware('ability:budgets:read');
        Route::get('/active', [BudgetController::class, 'active'])->middleware('ability:budgets:read');
        Route::get('/{budget}', [BudgetController::class, 'show'])->middleware('ability:budgets:read');
        Route::get('/{budget}/vs-actual', [BudgetController::class, 'vsActual'])->middleware('ability:budgets:read');
        Route::get('/{budget}/utilization', [BudgetController::class, 'utilization'])->middleware('ability:budgets:read');
        Route::get('/{budget}/forecast', [BudgetController::class, 'forecast'])->middleware('ability:budgets:read');
        Route::post('/', [BudgetController::class, 'store'])->middleware('ability:budgets:create');
        Route::put('/{budget}', [BudgetController::class, 'update'])->middleware('ability:budgets:update');
        Route::delete('/{budget}', [BudgetController::class, 'destroy'])->middleware('ability:budgets:delete');
        Route::post('/{budget}/clone', [BudgetController::class, 'clone'])->middleware('ability:budgets:create');
        Route::post('/{budget}/distribute', [BudgetController::class, 'distribute'])->middleware('ability:budgets:update');
    });

    // Banking & Imports (Phase 12)
    Route::prefix('imports')->group(function () {
        Route::get('/', [ImportController::class, 'index'])->middleware('ability:imports:read');
        Route::get('/{importBatch}', [ImportController::class, 'show'])->middleware('ability:imports:read');
        Route::post('/preview-csv', [ImportController::class, 'previewCSV'])->middleware('ability:imports:create');
        Route::post('/upload', [ImportController::class, 'upload'])->middleware('ability:imports:create');
        Route::post('/{importBatch}/process', [ImportController::class, 'process'])->middleware('ability:imports:create');
        Route::post('/match', [ImportController::class, 'match'])->middleware('ability:imports:read');
        Route::delete('/{importBatch}', [ImportController::class, 'destroy'])->middleware('ability:imports:delete');
    });

    // Scheduled Transactions (Phase 14)
    Route::prefix('scheduled-transactions')->group(function () {
        Route::get('/', [ScheduledTransactionController::class, 'index'])->middleware('ability:scheduled:read');
        Route::get('/upcoming', [ScheduledTransactionController::class, 'upcoming'])->middleware('ability:scheduled:read');
        Route::get('/due', [ScheduledTransactionController::class, 'due'])->middleware('ability:scheduled:read');
        Route::get('/summary', [ScheduledTransactionController::class, 'summary'])->middleware('ability:scheduled:read');
        Route::post('/process-all-due', [ScheduledTransactionController::class, 'processAllDue'])->middleware('ability:scheduled:update');
        Route::get('/{scheduledTransaction}', [ScheduledTransactionController::class, 'show'])->middleware('ability:scheduled:read');
        Route::post('/', [ScheduledTransactionController::class, 'store'])->middleware('ability:scheduled:create');
        Route::put('/{scheduledTransaction}', [ScheduledTransactionController::class, 'update'])->middleware('ability:scheduled:update');
        Route::delete('/{scheduledTransaction}', [ScheduledTransactionController::class, 'destroy'])->middleware('ability:scheduled:delete');
        Route::post('/{scheduledTransaction}/pause', [ScheduledTransactionController::class, 'pause'])->middleware('ability:scheduled:update');
        Route::post('/{scheduledTransaction}/resume', [ScheduledTransactionController::class, 'resume'])->middleware('ability:scheduled:update');
        Route::post('/{scheduledTransaction}/create-now', [ScheduledTransactionController::class, 'createNow'])->middleware('ability:scheduled:update');
        Route::post('/{scheduledTransaction}/skip-next', [ScheduledTransactionController::class, 'skipNext'])->middleware('ability:scheduled:update');
        Route::post('/{scheduledTransaction}/end-series', [ScheduledTransactionController::class, 'endSeries'])->middleware('ability:scheduled:update');
    });

    // Taxes (Phase 15)
    Route::prefix('taxes')->group(function () {
        Route::get('/', [TaxController::class, 'index'])->middleware('ability:taxes:read');
        Route::get('/applicable', [TaxController::class, 'applicable'])->middleware('ability:taxes:read');
        Route::get('/summary', [TaxController::class, 'summary'])->middleware('ability:taxes:read');
        Route::get('/recoverable', [TaxController::class, 'recoverable'])->middleware('ability:taxes:read');
        Route::post('/validate-tax-number', [TaxController::class, 'validateTaxNumber'])->middleware('ability:taxes:read');
        Route::get('/{tax}', [TaxController::class, 'show'])->middleware('ability:taxes:read');
        Route::post('/', [TaxController::class, 'store'])->middleware('ability:taxes:create');
        Route::put('/{tax}', [TaxController::class, 'update'])->middleware('ability:taxes:update');
        Route::delete('/{tax}', [TaxController::class, 'destroy'])->middleware('ability:taxes:delete');
    });

    // Sync & Offline (Phase 16)
    Route::prefix('sync')->group(function () {
        Route::get('/status', [\App\Http\Controllers\Api\SyncController::class, 'status']);
        Route::post('/batch', [\App\Http\Controllers\Api\SyncController::class, 'batch']);
        Route::get('/changes', [\App\Http\Controllers\Api\SyncController::class, 'changes']);
    });

    // Push Notifications (Phase 16)
    Route::prefix('push-subscriptions')->group(function () {
        Route::post('/', [\App\Http\Controllers\Api\PushSubscriptionController::class, 'store']);
        Route::delete('/', [\App\Http\Controllers\Api\PushSubscriptionController::class, 'destroy']);
    });
});
