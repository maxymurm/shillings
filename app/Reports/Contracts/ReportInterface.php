<?php

namespace App\Reports\Contracts;

use App\Models\Company;
use Carbon\Carbon;

/**
 * Base interface for all financial reports.
 */
interface ReportInterface
{
    /**
     * Get the report name.
     */
    public function getName(): string;

    /**
     * Get the report description.
     */
    public function getDescription(): string;

    /**
     * Generate the report data.
     */
    public function generate(): array;

    /**
     * Get the report data for rendering.
     */
    public function getData(): array;

    /**
     * Set the company for the report.
     */
    public function forCompany(Company $company): self;

    /**
     * Set date parameters for the report.
     */
    public function forPeriod(?Carbon $startDate, ?Carbon $endDate): self;

    /**
     * Check if report data is valid (e.g., balanced).
     */
    public function isValid(): bool;

    /**
     * Get validation errors if any.
     */
    public function getErrors(): array;
}
