<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChartOfAccountsController;
use App\Http\Controllers\Api\ExchangeRateController;
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
        Route::post('/', [AccountController::class, 'store'])->middleware('ability:accounts:create');
        Route::put('/{account}', [AccountController::class, 'update'])->middleware('ability:accounts:update');
        Route::delete('/{account}', [AccountController::class, 'destroy'])->middleware('ability:accounts:delete');
    });

    // Transactions
    Route::prefix('transactions')->group(function () {
        Route::get('/', [TransactionController::class, 'index'])->middleware('ability:transactions:read');
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
});
