<?php

namespace App\Reports;

use App\Models\Company;
use App\Reports\Contracts\ReportInterface;
use Carbon\Carbon;

/**
 * Base class for all financial reports.
 */
abstract class BaseReport implements ReportInterface
{
    protected ?Company $company = null;
    protected ?Carbon $startDate = null;
    protected ?Carbon $endDate = null;
    protected array $data = [];
    protected array $errors = [];
    protected bool $generated = false;

    /**
     * Get the report name.
     */
    abstract public function getName(): string;

    /**
     * Get the report description.
     */
    public function getDescription(): string
    {
        return '';
    }

    /**
     * Generate the report data (implemented by subclasses).
     */
    abstract public function generate(): array;

    /**
     * Get the report data.
     */
    public function getData(): array
    {
        if (! $this->generated) {
            $this->generate();
        }

        return $this->data;
    }

    /**
     * Set the company for the report.
     */
    public function forCompany(Company $company): self
    {
        $this->company = $company;
        $this->generated = false;

        return $this;
    }

    /**
     * Set date parameters.
     */
    public function forPeriod(?Carbon $startDate, ?Carbon $endDate): self
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->generated = false;

        return $this;
    }

    /**
     * Set as-of date (convenience for point-in-time reports).
     */
    public function asOf(Carbon $date): self
    {
        $this->endDate = $date;
        $this->generated = false;

        return $this;
    }

    /**
     * Check if report is valid.
     */
    public function isValid(): bool
    {
        return empty($this->errors);
    }

    /**
     * Get validation errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get the company.
     */
    protected function getCompany(): Company
    {
        if (! $this->company) {
            throw new \RuntimeException('Company not set for report');
        }

        return $this->company;
    }

    /**
     * Get company currency code.
     */
    protected function getCurrencyCode(): string
    {
        return $this->company?->defaultCurrency?->code ?? 'KES';
    }

    /**
     * Format a monetary value.
     */
    protected function formatMoney(float $amount): string
    {
        return number_format($amount, 2, '.', ',');
    }

    /**
     * Get the effective end date.
     */
    protected function getEndDate(): Carbon
    {
        return $this->endDate ?? now();
    }

    /**
     * Get the effective start date.
     */
    protected function getStartDate(): Carbon
    {
        return $this->startDate ?? now()->startOfYear();
    }

    /**
     * Get report metadata.
     */
    public function getMetadata(): array
    {
        return [
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'company' => $this->company?->name,
            'company_id' => $this->company?->id,
            'currency' => $this->getCurrencyCode(),
            'start_date' => $this->startDate?->toDateString(),
            'end_date' => $this->endDate?->toDateString(),
            'generated_at' => now()->toIso8601String(),
            'is_valid' => $this->isValid(),
        ];
    }

    /**
     * Convert report to array for JSON/API response.
     */
    public function toArray(): array
    {
        return [
            'metadata' => $this->getMetadata(),
            'data' => $this->getData(),
            'errors' => $this->getErrors(),
        ];
    }
}
