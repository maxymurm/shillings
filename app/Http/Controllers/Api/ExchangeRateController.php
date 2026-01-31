<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExchangeRate;
use App\Services\ExchangeRateService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExchangeRateController extends Controller
{
    public function __construct(
        private ExchangeRateService $exchangeRateService
    ) {}

    /**
     * List exchange rates.
     *
     * GET /api/exchange-rates
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_currency_id' => 'sometimes|uuid|exists:currencies,id',
            'to_currency_id' => 'sometimes|uuid|exists:currencies,id',
            'date' => 'sometimes|date',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $query = ExchangeRate::with(['fromCurrency', 'toCurrency']);

        if (isset($validated['from_currency_id'])) {
            $query->where('from_currency_id', $validated['from_currency_id']);
        }

        if (isset($validated['to_currency_id'])) {
            $query->where('to_currency_id', $validated['to_currency_id']);
        }

        if (isset($validated['date'])) {
            $query->where('effective_date', $validated['date']);
        }

        if (isset($validated['start_date'])) {
            $query->where('effective_date', '>=', $validated['start_date']);
        }

        if (isset($validated['end_date'])) {
            $query->where('effective_date', '<=', $validated['end_date']);
        }

        $rates = $query->orderBy('effective_date', 'desc')
            ->paginate($validated['per_page'] ?? 15);

        return response()->json([
            'success' => true,
            'data' => $rates,
        ]);
    }

    /**
     * Get a specific exchange rate.
     *
     * GET /api/exchange-rates/{rate}
     */
    public function show(ExchangeRate $exchangeRate): JsonResponse
    {
        $exchangeRate->load(['fromCurrency', 'toCurrency']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $exchangeRate->id,
                'from_currency' => $exchangeRate->fromCurrency,
                'to_currency' => $exchangeRate->toCurrency,
                'rate' => $exchangeRate->getRate(),
                'rate_num' => $exchangeRate->rate_num,
                'rate_denom' => $exchangeRate->rate_denom,
                'effective_date' => $exchangeRate->effective_date->toDateString(),
                'source' => $exchangeRate->source,
            ],
        ]);
    }

    /**
     * Store a new exchange rate.
     *
     * POST /api/exchange-rates
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_currency_id' => 'required|uuid|exists:currencies,id',
            'to_currency_id' => [
                'required',
                'uuid',
                'exists:currencies,id',
                'different:from_currency_id',
            ],
            'rate' => 'required|numeric|gt:0',
            'effective_date' => 'required|date',
            'source' => 'sometimes|string|max:100',
        ]);

        $rate = $this->exchangeRateService->storeRate(
            $validated['from_currency_id'],
            $validated['to_currency_id'],
            $validated['rate'],
            Carbon::parse($validated['effective_date']),
            $validated['source'] ?? 'api'
        );

        return response()->json([
            'success' => true,
            'data' => $rate->load(['fromCurrency', 'toCurrency']),
        ], 201);
    }

    /**
     * Update an exchange rate.
     *
     * PUT /api/exchange-rates/{rate}
     */
    public function update(Request $request, ExchangeRate $exchangeRate): JsonResponse
    {
        $validated = $request->validate([
            'rate' => 'sometimes|numeric|gt:0',
            'source' => 'sometimes|string|max:100',
        ]);

        if (isset($validated['rate'])) {
            $exchangeRate->rate_num = (int) round($validated['rate'] * ExchangeRate::DEFAULT_DENOM);
            $exchangeRate->rate_denom = ExchangeRate::DEFAULT_DENOM;
        }

        if (isset($validated['source'])) {
            $exchangeRate->source = $validated['source'];
        }

        $exchangeRate->save();

        return response()->json([
            'success' => true,
            'data' => $exchangeRate->load(['fromCurrency', 'toCurrency']),
        ]);
    }

    /**
     * Delete an exchange rate.
     *
     * DELETE /api/exchange-rates/{rate}
     */
    public function destroy(ExchangeRate $exchangeRate): JsonResponse
    {
        $exchangeRate->delete();

        return response()->json([
            'success' => true,
            'message' => 'Exchange rate deleted successfully',
        ]);
    }

    /**
     * Convert amount between currencies.
     *
     * POST /api/exchange-rates/convert
     */
    public function convert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_currency_id' => 'required|uuid|exists:currencies,id',
            'to_currency_id' => 'required|uuid|exists:currencies,id',
            'amount' => 'required|numeric',
            'date' => 'sometimes|date',
        ]);

        $date = isset($validated['date']) ? Carbon::parse($validated['date']) : null;

        $rate = $this->exchangeRateService->getRate(
            $validated['from_currency_id'],
            $validated['to_currency_id'],
            $date
        );

        if (! $rate) {
            return response()->json([
                'success' => false,
                'error' => 'No exchange rate available for this currency pair',
            ], 404);
        }

        $convertedAmount = $validated['amount'] * $rate->getRate();

        return response()->json([
            'success' => true,
            'data' => [
                'original_amount' => $validated['amount'],
                'converted_amount' => round($convertedAmount, 6),
                'rate' => $rate->getRate(),
                'effective_date' => $rate->effective_date->toDateString(),
            ],
        ]);
    }

    /**
     * Get rate for a currency pair.
     *
     * GET /api/exchange-rates/pair/{from}/{to}
     */
    public function getPairRate(Request $request, string $from, string $to): JsonResponse
    {
        $date = $request->query('date')
            ? Carbon::parse($request->query('date'))
            : null;

        $rate = $this->exchangeRateService->getRate($from, $to, $date);

        if (! $rate) {
            return response()->json([
                'success' => false,
                'error' => 'No exchange rate available for this currency pair',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'from_currency_id' => $from,
                'to_currency_id' => $to,
                'rate' => $rate->getRate(),
                'effective_date' => $rate->effective_date?->toDateString(),
                'source' => $rate->source,
            ],
        ]);
    }
}
