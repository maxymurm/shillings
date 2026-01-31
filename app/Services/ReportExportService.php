<?php

namespace App\Services;

use App\Reports\Contracts\ReportInterface;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Report Export Service (Issue #34)
 *
 * Handles exporting reports to PDF, Excel, and CSV formats.
 */
class ReportExportService
{
    /**
     * Export a report to the specified format.
     */
    public function export(ReportInterface $report, string $format): string
    {
        if (! $report->isValid()) {
            throw new \InvalidArgumentException('Report is not valid for export: ' . implode(', ', $report->getErrors()));
        }

        if (! in_array($format, ['pdf', 'excel', 'csv'])) {
            throw new \InvalidArgumentException("Unsupported export format: {$format}");
        }

        return match ($format) {
            'pdf' => $this->exportToPdf($report),
            'excel' => $this->exportToExcel($report),
            'csv' => $this->exportToCsv($report),
        };
    }

    /**
     * Export report to PDF.
     */
    public function exportToPdf(ReportInterface $report): string
    {
        $data = $report->toArray();

        $html = $this->generatePdfHtml($report, $data);

        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('a4', 'portrait');

        $filename = $this->generateFilename($report, 'pdf');
        $path = "reports/{$filename}";

        Storage::disk('local')->put($path, $pdf->output());

        return Storage::disk('local')->path($path);
    }

    /**
     * Export report to Excel.
     */
    public function exportToExcel(ReportInterface $report): string
    {
        $data = $report->toArray();
        $exportData = $this->prepareExcelData($report, $data);

        $filename = $this->generateFilename($report, 'xlsx');
        $path = "reports/{$filename}";

        $export = new class($exportData, $report->getName()) implements FromArray, WithHeadings, WithStyles, WithTitle
        {
            public function __construct(
                private array $data,
                private string $title
            ) {}

            public function array(): array
            {
                return $this->data['rows'] ?? [];
            }

            public function headings(): array
            {
                return $this->data['headers'] ?? [];
            }

            public function title(): string
            {
                return substr($this->title, 0, 31); // Excel sheet name max length
            }

            public function styles(Worksheet $sheet)
            {
                return [
                    1 => ['font' => ['bold' => true]],
                ];
            }
        };

        Excel::store($export, $path, 'local');

        return Storage::disk('local')->path($path);
    }

    /**
     * Export report to CSV.
     */
    public function exportToCsv(ReportInterface $report): string
    {
        $data = $report->toArray();
        $exportData = $this->prepareCsvData($report, $data);

        $filename = $this->generateFilename($report, 'csv');
        $path = "reports/{$filename}";

        $output = fopen('php://temp', 'r+');

        // Write headers
        if (! empty($exportData['headers'])) {
            fputcsv($output, $exportData['headers']);
        }

        // Write rows
        foreach ($exportData['rows'] as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        Storage::disk('local')->put($path, $csv);

        return Storage::disk('local')->path($path);
    }

    /**
     * Generate HTML for PDF export.
     */
    protected function generatePdfHtml(ReportInterface $report, array $data): string
    {
        $metadata = $data['metadata'] ?? [];
        $reportData = $data['data'] ?? [];

        $html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{TITLE}}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 18pt;
        }
        .header .subtitle {
            font-size: 12pt;
            color: #666;
        }
        .header .period {
            font-size: 10pt;
            color: #888;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            padding: 6px 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
            border-top: 2px solid #333;
        }
        .section-header {
            font-weight: bold;
            background-color: #e9e9e9;
            padding: 8px;
            margin-top: 15px;
        }
        .footer {
            margin-top: 20px;
            font-size: 8pt;
            color: #888;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{TITLE}}</h1>
        <div class="subtitle">{{COMPANY}}</div>
        <div class="period">{{PERIOD}}</div>
    </div>
    {{CONTENT}}
    <div class="footer">
        Generated on {{GENERATED_AT}} | Currency: {{CURRENCY}}
    </div>
</body>
</html>
HTML;

        // Replace placeholders
        $html = str_replace('{{TITLE}}', htmlspecialchars($report->getName()), $html);
        $html = str_replace('{{COMPANY}}', htmlspecialchars($metadata['company_name'] ?? ''), $html);
        $html = str_replace('{{PERIOD}}', $this->formatPeriod($metadata), $html);
        $html = str_replace('{{GENERATED_AT}}', $metadata['generated_at'] ?? now()->toDateTimeString(), $html);
        $html = str_replace('{{CURRENCY}}', $metadata['currency_code'] ?? 'USD', $html);
        $html = str_replace('{{CONTENT}}', $this->generateReportContent($report, $reportData), $html);

        return $html;
    }

    /**
     * Generate report-specific HTML content.
     */
    protected function generateReportContent(ReportInterface $report, array $data): string
    {
        $name = $report->getName();

        return match (true) {
            str_contains($name, 'Trial Balance') => $this->generateTrialBalanceHtml($data),
            str_contains($name, 'Balance Sheet') => $this->generateBalanceSheetHtml($data),
            str_contains($name, 'Income Statement') => $this->generateIncomeStatementHtml($data),
            str_contains($name, 'Cash Flow') => $this->generateCashFlowHtml($data),
            str_contains($name, 'General Ledger') => $this->generateGeneralLedgerHtml($data),
            default => $this->generateGenericHtml($data),
        };
    }

    /**
     * Generate Trial Balance HTML.
     */
    protected function generateTrialBalanceHtml(array $data): string
    {
        $html = '<table><thead><tr><th>Account</th><th>Code</th><th class="text-right">Debit</th><th class="text-right">Credit</th></tr></thead><tbody>';

        foreach ($data['accounts'] ?? [] as $account) {
            $html .= sprintf(
                '<tr><td>%s</td><td>%s</td><td class="text-right">%s</td><td class="text-right">%s</td></tr>',
                htmlspecialchars($account['name'] ?? ''),
                htmlspecialchars($account['code'] ?? ''),
                $account['debit_formatted'] ?? '',
                $account['credit_formatted'] ?? ''
            );
        }

        $totals = $data['totals'] ?? [];
        $html .= sprintf(
            '<tr class="total-row"><td colspan="2">Total</td><td class="text-right">%s</td><td class="text-right">%s</td></tr>',
            $totals['total_debit_formatted'] ?? '',
            $totals['total_credit_formatted'] ?? ''
        );

        $html .= '</tbody></table>';

        return $html;
    }

    /**
     * Generate Balance Sheet HTML.
     */
    protected function generateBalanceSheetHtml(array $data): string
    {
        $html = '';

        foreach (['assets', 'liabilities', 'equity'] as $section) {
            if (isset($data[$section])) {
                $html .= '<div class="section-header">' . ucfirst($section) . '</div>';
                $html .= '<table><tbody>';

                foreach ($data[$section]['accounts'] ?? [] as $account) {
                    $html .= sprintf(
                        '<tr><td>%s</td><td class="text-right">%s</td></tr>',
                        htmlspecialchars($account['name'] ?? ''),
                        $account['balance_formatted'] ?? ''
                    );
                }

                $html .= sprintf(
                    '<tr class="total-row"><td>Total %s</td><td class="text-right">%s</td></tr>',
                    ucfirst($section),
                    $data[$section]['total_formatted'] ?? ''
                );

                $html .= '</tbody></table>';
            }
        }

        return $html;
    }

    /**
     * Generate Income Statement HTML.
     */
    protected function generateIncomeStatementHtml(array $data): string
    {
        $html = '';

        foreach (['revenue', 'expenses'] as $section) {
            if (isset($data[$section])) {
                $html .= '<div class="section-header">' . ucfirst($section) . '</div>';
                $html .= '<table><tbody>';

                foreach ($data[$section]['accounts'] ?? [] as $account) {
                    $html .= sprintf(
                        '<tr><td>%s</td><td class="text-right">%s</td></tr>',
                        htmlspecialchars($account['name'] ?? ''),
                        $account['balance_formatted'] ?? ''
                    );
                }

                $html .= sprintf(
                    '<tr class="total-row"><td>Total %s</td><td class="text-right">%s</td></tr>',
                    ucfirst($section),
                    $data[$section]['total_formatted'] ?? ''
                );

                $html .= '</tbody></table>';
            }
        }

        if (isset($data['net_income'])) {
            $html .= sprintf(
                '<div class="section-header">Net Income: %s</div>',
                $data['net_income_formatted'] ?? ''
            );
        }

        return $html;
    }

    /**
     * Generate Cash Flow HTML.
     */
    protected function generateCashFlowHtml(array $data): string
    {
        $html = '';

        foreach (['operating_activities', 'investing_activities', 'financing_activities'] as $section) {
            if (isset($data[$section])) {
                $html .= '<div class="section-header">' . ($data[$section]['label'] ?? ucfirst($section)) . '</div>';
                $html .= '<table><tbody>';

                $items = $data[$section]['items'] ?? $data[$section]['adjustments'] ?? [];
                foreach ($items as $item) {
                    $html .= sprintf(
                        '<tr><td>%s</td><td class="text-right">%s</td></tr>',
                        htmlspecialchars($item['name'] ?? ''),
                        $item['amount_formatted'] ?? ''
                    );
                }

                $html .= sprintf(
                    '<tr class="total-row"><td>Net Cash</td><td class="text-right">%s</td></tr>',
                    $data[$section]['net_cash_formatted'] ?? ''
                );

                $html .= '</tbody></table>';
            }
        }

        if (isset($data['summary'])) {
            $html .= '<div class="section-header">Summary</div>';
            $html .= '<table><tbody>';
            $html .= sprintf('<tr><td>Net Change in Cash</td><td class="text-right">%s</td></tr>', $data['summary']['net_change_formatted'] ?? '');
            $html .= sprintf('<tr><td>Beginning Cash</td><td class="text-right">%s</td></tr>', $data['summary']['beginning_cash_formatted'] ?? '');
            $html .= sprintf('<tr class="total-row"><td>Ending Cash</td><td class="text-right">%s</td></tr>', $data['summary']['ending_cash_formatted'] ?? '');
            $html .= '</tbody></table>';
        }

        return $html;
    }

    /**
     * Generate General Ledger HTML.
     */
    protected function generateGeneralLedgerHtml(array $data): string
    {
        $html = '';

        foreach ($data['accounts'] ?? [] as $account) {
            $html .= '<div class="section-header">' . htmlspecialchars($account['account_code'] . ' - ' . $account['account_name']) . '</div>';
            $html .= '<table><thead><tr><th>Date</th><th>Description</th><th class="text-right">Debit</th><th class="text-right">Credit</th><th class="text-right">Balance</th></tr></thead><tbody>';

            // Opening balance
            $html .= sprintf(
                '<tr><td></td><td>Opening Balance</td><td></td><td></td><td class="text-right">%s</td></tr>',
                $account['opening_balance_formatted'] ?? ''
            );

            foreach ($account['transactions'] ?? [] as $txn) {
                $html .= sprintf(
                    '<tr><td>%s</td><td>%s</td><td class="text-right">%s</td><td class="text-right">%s</td><td class="text-right">%s</td></tr>',
                    $txn['date'] ?? '',
                    htmlspecialchars($txn['description'] ?? ''),
                    $txn['debit_formatted'] ?? '',
                    $txn['credit_formatted'] ?? '',
                    $txn['balance_formatted'] ?? ''
                );
            }

            $html .= sprintf(
                '<tr class="total-row"><td></td><td>Closing Balance</td><td class="text-right">%s</td><td class="text-right">%s</td><td class="text-right">%s</td></tr>',
                $account['period_debit_formatted'] ?? '',
                $account['period_credit_formatted'] ?? '',
                $account['closing_balance_formatted'] ?? ''
            );

            $html .= '</tbody></table>';
        }

        return $html;
    }

    /**
     * Generate generic HTML for unknown report types.
     */
    protected function generateGenericHtml(array $data): string
    {
        return '<pre>' . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . '</pre>';
    }

    /**
     * Prepare data for Excel export.
     */
    protected function prepareExcelData(ReportInterface $report, array $data): array
    {
        $name = $report->getName();

        return match (true) {
            str_contains($name, 'Trial Balance') => $this->prepareTrialBalanceExcel($data['data'] ?? []),
            str_contains($name, 'Balance Sheet') => $this->prepareBalanceSheetExcel($data['data'] ?? []),
            str_contains($name, 'Income Statement') => $this->prepareIncomeStatementExcel($data['data'] ?? []),
            str_contains($name, 'General Ledger') => $this->prepareGeneralLedgerExcel($data['data'] ?? []),
            default => $this->prepareGenericExcel($data['data'] ?? []),
        };
    }

    /**
     * Prepare data for CSV export.
     */
    protected function prepareCsvData(ReportInterface $report, array $data): array
    {
        return $this->prepareExcelData($report, $data);
    }

    /**
     * Prepare Trial Balance for Excel.
     */
    protected function prepareTrialBalanceExcel(array $data): array
    {
        $headers = ['Account Code', 'Account Name', 'Debit', 'Credit'];
        $rows = [];

        foreach ($data['accounts'] ?? [] as $account) {
            $rows[] = [
                $account['code'] ?? '',
                $account['name'] ?? '',
                $account['debit'] ?? '',
                $account['credit'] ?? '',
            ];
        }

        $totals = $data['totals'] ?? [];
        $rows[] = ['', 'TOTAL', $totals['total_debit'] ?? '', $totals['total_credit'] ?? ''];

        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * Prepare Balance Sheet for Excel.
     */
    protected function prepareBalanceSheetExcel(array $data): array
    {
        $headers = ['Section', 'Account Name', 'Balance'];
        $rows = [];

        foreach (['assets', 'liabilities', 'equity'] as $section) {
            foreach ($data[$section]['accounts'] ?? [] as $account) {
                $rows[] = [
                    ucfirst($section),
                    $account['name'] ?? '',
                    $account['balance'] ?? '',
                ];
            }
            $rows[] = ['', 'Total ' . ucfirst($section), $data[$section]['total'] ?? ''];
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * Prepare Income Statement for Excel.
     */
    protected function prepareIncomeStatementExcel(array $data): array
    {
        $headers = ['Section', 'Account Name', 'Amount', 'Percentage'];
        $rows = [];

        foreach (['revenue', 'expenses'] as $section) {
            foreach ($data[$section]['accounts'] ?? [] as $account) {
                $rows[] = [
                    ucfirst($section),
                    $account['name'] ?? '',
                    $account['balance'] ?? '',
                    ($account['percentage'] ?? '') . '%',
                ];
            }
            $rows[] = ['', 'Total ' . ucfirst($section), $data[$section]['total'] ?? '', ''];
        }

        $rows[] = ['', 'Net Income', $data['net_income'] ?? '', ''];

        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * Prepare General Ledger for Excel.
     */
    protected function prepareGeneralLedgerExcel(array $data): array
    {
        $headers = ['Account', 'Date', 'Description', 'Debit', 'Credit', 'Balance'];
        $rows = [];

        foreach ($data['accounts'] ?? [] as $account) {
            $accountLabel = ($account['account_code'] ?? '') . ' - ' . ($account['account_name'] ?? '');
            $rows[] = [$accountLabel, '', 'Opening Balance', '', '', $account['opening_balance'] ?? ''];

            foreach ($account['transactions'] ?? [] as $txn) {
                $rows[] = [
                    '',
                    $txn['date'] ?? '',
                    $txn['description'] ?? '',
                    $txn['debit'] ?? '',
                    $txn['credit'] ?? '',
                    $txn['balance'] ?? '',
                ];
            }

            $rows[] = ['', '', 'Closing Balance', $account['period_debit'] ?? '', $account['period_credit'] ?? '', $account['closing_balance'] ?? ''];
            $rows[] = []; // Empty row between accounts
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * Prepare generic data for Excel.
     */
    protected function prepareGenericExcel(array $data): array
    {
        $headers = array_keys($data);
        $rows = [array_values($data)];

        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * Format period string for display.
     */
    protected function formatPeriod(array $metadata): string
    {
        $start = $metadata['start_date'] ?? '';
        $end = $metadata['end_date'] ?? '';

        if ($start && $end) {
            return "Period: {$start} to {$end}";
        }

        if ($end) {
            return "As of: {$end}";
        }

        return '';
    }

    /**
     * Generate a unique filename.
     */
    protected function generateFilename(ReportInterface $report, string $extension): string
    {
        $name = str_replace(' ', '_', strtolower($report->getName()));
        $timestamp = now()->format('Y-m-d_His');

        return "{$name}_{$timestamp}.{$extension}";
    }
}
