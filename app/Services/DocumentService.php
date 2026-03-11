<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\Contact;
use App\Models\Account;
use App\Models\Company;
use App\Models\Split;
use App\Models\Transaction;
use App\ValueObjects\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DocumentService
{
    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Create a new document with items.
     */
    public function create(array $data, array $items): Document
    {
        return DB::transaction(function () use ($data, $items) {
            // Generate document number if not provided
            if (empty($data['document_number'])) {
                $data['document_number'] = $this->generateDocumentNumber(
                    $data['company_id'],
                    $data['type']
                );
            }

            $document = Document::create($data);

            foreach ($items as $itemData) {
                $itemData['document_id'] = $document->id;
                DocumentItem::create($itemData);
            }

            $document->calculateTotals();

            return $document->fresh(['items', 'contact']);
        });
    }

    /**
     * Update a document with items.
     */
    public function update(Document $document, array $data, ?array $items = null): Document
    {
        if (!$document->isEditable()) {
            throw new \Exception('Cannot edit a document that has been paid or cancelled.');
        }

        return DB::transaction(function () use ($document, $data, $items) {
            $document->update($data);

            if ($items !== null) {
                $this->syncItems($document, $items);
            }

            $document->calculateTotals();

            return $document->fresh(['items', 'contact']);
        });
    }

    /**
     * Sync document items.
     */
    protected function syncItems(Document $document, array $items): void
    {
        $existingIds = collect($items)->pluck('id')->filter()->toArray();
        
        // Delete removed items
        $document->items()->whereNotIn('id', $existingIds)->delete();

        foreach ($items as $itemData) {
            $itemData['document_id'] = $document->id;
            
            if (isset($itemData['id'])) {
                $document->items()->where('id', $itemData['id'])->update($itemData);
            } else {
                DocumentItem::create($itemData);
            }
        }
    }

    /**
     * Generate a unique document number.
     */
    public function generateDocumentNumber(string $companyId, string $type): string
    {
        $prefixes = [
            'invoice' => 'INV',
            'bill' => 'BILL',
            'quote' => 'QUO',
            'credit_note' => 'CN',
            'debit_note' => 'DN',
            'receipt' => 'REC',
        ];

        $prefix = $prefixes[$type] ?? 'DOC';
        $year = date('Y');
        
        $lastNumber = Document::where('company_id', $companyId)
            ->where('type', $type)
            ->whereYear('created_at', $year)
            ->max(DB::raw("CAST(SUBSTR(document_number, -6) AS INTEGER)")) ?? 0;

        return sprintf('%s-%s-%06d', $prefix, $year, $lastNumber + 1);
    }

    /**
     * Convert a quote to an invoice.
     */
    public function convertQuoteToInvoice(Document $quote): Document
    {
        if ($quote->type !== 'quote') {
            throw new \Exception('Only quotes can be converted to invoices.');
        }

        return DB::transaction(function () use ($quote) {
            $invoiceData = $quote->toArray();
            $invoiceData['type'] = 'invoice';
            $invoiceData['document_number'] = $this->generateDocumentNumber(
                $quote->company_id,
                'invoice'
            );
            $invoiceData['status'] = 'draft';
            $invoiceData['issued_at'] = now();
            $invoiceData['parent_id'] = $quote->id;
            unset($invoiceData['id'], $invoiceData['created_at'], $invoiceData['updated_at']);

            $invoice = Document::create($invoiceData);

            foreach ($quote->items as $item) {
                $itemData = $item->toArray();
                $itemData['document_id'] = $invoice->id;
                unset($itemData['id'], $itemData['created_at'], $itemData['updated_at']);
                DocumentItem::create($itemData);
            }

            $invoice->calculateTotals();

            // Mark quote as converted
            $quote->update(['status' => 'converted']);

            return $invoice->fresh(['items', 'contact']);
        });
    }

    /**
     * Duplicate a document.
     */
    public function duplicate(Document $document): Document
    {
        return DB::transaction(function () use ($document) {
            $newData = $document->toArray();
            $newData['document_number'] = $this->generateDocumentNumber(
                $document->company_id,
                $document->type
            );
            $newData['status'] = 'draft';
            $newData['issued_at'] = now();
            $newData['due_at'] = now()->addDays(30); // Reset due date
            $newData['sent_at'] = null;
            $newData['paid_at'] = null;
            $newData['paid_amount_num'] = 0;
            $newData['paid_amount_denom'] = 1;
            $newData['amount_paid_num'] = 0;
            $newData['amount_paid_denom'] = 100;
            unset($newData['id'], $newData['created_at'], $newData['updated_at'], $newData['deleted_at']);

            $newDocument = Document::create($newData);

            foreach ($document->items as $item) {
                $itemData = $item->toArray();
                $itemData['document_id'] = $newDocument->id;
                unset($itemData['id'], $itemData['created_at'], $itemData['updated_at']);
                DocumentItem::create($itemData);
            }

            $newDocument->calculateTotals();

            return $newDocument->fresh(['items', 'contact']);
        });
    }

    /**
     * Record a payment for a document.
     */
    public function recordPayment(
        Document $document,
        Money $amount,
        Account $paymentAccount,
        ?Carbon $paymentDate = null,
        ?string $reference = null
    ): Transaction {
        if ($document->status === 'cancelled' || $document->status === 'paid') {
            throw new \Exception('Cannot record payment for this document.');
        }

        $paymentDate = $paymentDate ?? now();
        $total = $document->getTotal();
        $paid = $document->getPaidAmount();
        $remaining = $total->subtract($paid);

        if ($amount->greaterThan($remaining)) {
            throw new \Exception('Payment amount exceeds remaining balance.');
        }

        return DB::transaction(function () use ($document, $amount, $paymentAccount, $paymentDate, $reference, $remaining) {
            // Record the payment on document
            $document->recordPayment($amount);

            // Create accounting transaction
            $isIncome = in_array($document->type, ['invoice', 'debit_note']);
            $amountDecimal = $amount->toDecimal();
            
            // Get AR/AP account - fall back to paymentAccount if not found
            $receivableAccount = Account::where('company_id', $document->company_id)
                ->where('code', 'like', '1200%') // Accounts Receivable
                ->first() ?? $paymentAccount;
            
            $payableAccount = Account::where('company_id', $document->company_id)
                ->where('code', 'like', '2000%') // Accounts Payable
                ->first() ?? $paymentAccount;

            $splits = [];
            
            if ($isIncome) {
                // Payment received: Debit Cash, Credit AR
                $splits[] = [
                    'account_id' => $paymentAccount->id,
                    'amount' => $amountDecimal,
                    'action' => Split::DEBIT,
                    'memo' => "Payment for {$document->document_number}",
                ];
                $splits[] = [
                    'account_id' => $receivableAccount->id,
                    'amount' => $amountDecimal,
                    'action' => Split::CREDIT,
                    'memo' => "Payment for {$document->document_number}",
                ];
            } else {
                // Payment made: Debit AP, Credit Cash
                $splits[] = [
                    'account_id' => $payableAccount->id,
                    'amount' => $amountDecimal,
                    'action' => Split::DEBIT,
                    'memo' => "Payment for {$document->document_number}",
                ];
                $splits[] = [
                    'account_id' => $paymentAccount->id,
                    'amount' => $amountDecimal,
                    'action' => Split::CREDIT,
                    'memo' => "Payment for {$document->document_number}",
                ];
            }

            $transaction = $this->transactionService->create([
                'company_id' => $document->company_id,
                'transaction_date' => $paymentDate,
                'description' => "Payment: {$document->document_number}" . ($reference ? " (Ref: {$reference})" : ''),
            ], $splits);

            return $transaction;
        });
    }

    /**
     * Send document (mark as sent).
     */
    public function send(Document $document): Document
    {
        if (!in_array($document->status, ['draft'])) {
            throw new \Exception('Only draft documents can be sent.');
        }

        $document->markAsSent();

        // Here you would integrate with email service
        // Mail::to($document->contact->email)->send(new DocumentMail($document));

        return $document;
    }

    /**
     * Cancel a document.
     */
    public function cancel(Document $document, string $reason): Document
    {
        if ($document->status === 'paid') {
            throw new \Exception('Cannot cancel a fully paid document.');
        }

        return DB::transaction(function () use ($document, $reason) {
            $document->cancel($reason);
            return $document;
        });
    }

    /**
     * Create a credit note for an invoice.
     */
    public function createCreditNote(Document $invoice, array $items, ?string $reason = null): Document
    {
        if ($invoice->type !== 'invoice') {
            throw new \Exception('Credit notes can only be created for invoices.');
        }

        return DB::transaction(function () use ($invoice, $items, $reason) {
            $creditNoteData = [
                'company_id' => $invoice->company_id,
                'type' => 'credit_note',
                'document_number' => $this->generateDocumentNumber($invoice->company_id, 'credit_note'),
                'contact_id' => $invoice->contact_id,
                'status' => 'draft',
                'issued_at' => now(),
                'due_at' => now()->addDays(30),
                'currency_code' => $invoice->currency_code,
                'notes' => $reason ?? "Credit note for invoice {$invoice->document_number}",
                'parent_id' => $invoice->id,
            ];

            $creditNote = Document::create($creditNoteData);

            foreach ($items as $itemData) {
                $itemData['document_id'] = $creditNote->id;
                // Ensure name field is set (fall back to description)
                if (empty($itemData['name']) && !empty($itemData['description'])) {
                    $itemData['name'] = $itemData['description'];
                }
                DocumentItem::create($itemData);
            }

            $creditNote->calculateTotals();

            return $creditNote->fresh(['items', 'contact']);
        });
    }

    /**
     * Get overdue documents.
     */
    public function getOverdueDocuments(Company $company, ?string $type = null): Collection
    {
        return Document::query()
            ->where('company_id', $company->id)
            ->when($type, fn ($q) => $q->where('type', $type))
            ->whereIn('status', ['sent', 'partial'])
            ->where('due_at', '<', now())
            ->with(['contact'])
            ->orderBy('due_at')
            ->get();
    }

    /**
     * Update overdue statuses.
     */
    public function updateOverdueStatuses(Company $company): int
    {
        return Document::query()
            ->where('company_id', $company->id)
            ->whereIn('status', ['sent', 'partial'])
            ->where('due_at', '<', now())
            ->update(['status' => 'overdue']);
    }

    /**
     * Get document summary for dashboard.
     */
    public function getSummary(Company $company): array
    {
        $currency = $company->currency_code ?? 'KES';
        
        $invoices = Document::where('company_id', $company->id)
            ->where('type', 'invoice')
            ->whereIn('status', ['sent', 'partial', 'overdue', 'paid'])
            ->get();

        $bills = Document::where('company_id', $company->id)
            ->where('type', 'bill')
            ->whereIn('status', ['sent', 'partial', 'overdue', 'paid'])
            ->get();

        $quotes = Document::where('company_id', $company->id)
            ->where('type', 'quote')
            ->whereIn('status', ['draft', 'sent', 'accepted', 'declined'])
            ->get();

        // Calculate invoice totals
        $invoiceTotal = Money::zero($currency);
        $invoicePaid = Money::zero($currency);
        foreach ($invoices as $inv) {
            $invoiceTotal = $invoiceTotal->add($inv->getTotal());
            $invoicePaid = $invoicePaid->add($inv->getPaidAmount());
        }

        // Calculate bill totals
        $billTotal = Money::zero($currency);
        $billPaid = Money::zero($currency);
        foreach ($bills as $bill) {
            $billTotal = $billTotal->add($bill->getTotal());
            $billPaid = $billPaid->add($bill->getPaidAmount());
        }

        // Calculate quote totals
        $quoteTotal = Money::zero($currency);
        foreach ($quotes as $quote) {
            $quoteTotal = $quoteTotal->add($quote->getTotal());
        }

        return [
            'invoices' => [
                'count' => $invoices->count(),
                'total_num' => $invoiceTotal->getNumerator(),
                'total_denom' => $invoiceTotal->getDenominator(),
                'paid_num' => $invoicePaid->getNumerator(),
                'paid_denom' => $invoicePaid->getDenominator(),
            ],
            'bills' => [
                'count' => $bills->count(),
                'total_num' => $billTotal->getNumerator(),
                'total_denom' => $billTotal->getDenominator(),
                'paid_num' => $billPaid->getNumerator(),
                'paid_denom' => $billPaid->getDenominator(),
            ],
            'quotes' => [
                'count' => $quotes->count(),
                'total_num' => $quoteTotal->getNumerator(),
                'total_denom' => $quoteTotal->getDenominator(),
            ],
        ];
    }
}
