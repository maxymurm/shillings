<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\ChartOfAccountsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChartOfAccountsController extends Controller
{
    public function __construct(
        private ChartOfAccountsService $chartService
    ) {}

    /**
     * List available chart of accounts templates.
     *
     * GET /api/chart-of-accounts/templates
     */
    public function templates(): JsonResponse
    {
        $templates = $this->chartService->getAvailableTemplates();

        return response()->json([
            'success' => true,
            'data' => $templates,
        ]);
    }

    /**
     * Get template details.
     *
     * GET /api/chart-of-accounts/templates/{key}
     */
    public function showTemplate(string $key): JsonResponse
    {
        $template = $this->chartService->getTemplate($key);

        if (! $template) {
            return response()->json([
                'success' => false,
                'error' => 'Template not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $template,
        ]);
    }

    /**
     * Preview what would be imported.
     *
     * GET /api/chart-of-accounts/preview/{key}
     */
    public function preview(Request $request, string $key): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|uuid|exists:companies,id',
        ]);

        $company = Company::findOrFail($validated['company_id']);

        try {
            $preview = $this->chartService->previewImport($company, $key);

            return response()->json([
                'success' => true,
                'data' => $preview,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Import a template into a company.
     *
     * POST /api/chart-of-accounts/import
     */
    public function import(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'template' => 'required|string',
            'company_id' => 'required|uuid|exists:companies,id',
            'skip_existing' => 'sometimes|boolean',
        ]);

        $company = Company::findOrFail($validated['company_id']);
        $skipExisting = $validated['skip_existing'] ?? true;

        try {
            $stats = $this->chartService->importTemplate(
                $company,
                $validated['template'],
                $skipExisting
            );

            return response()->json([
                'success' => true,
                'message' => 'Template imported successfully',
                'data' => $stats,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
