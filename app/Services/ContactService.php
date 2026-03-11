<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\ContactPerson;
use App\Models\Address;
use App\Models\Company;
use App\ValueObjects\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ContactService
{
    /**
     * Create a new contact with optional persons and addresses.
     */
    public function create(array $data, ?array $persons = null, ?array $addresses = null): Contact
    {
        return DB::transaction(function () use ($data, $persons, $addresses) {
            $contact = Contact::create($data);

            if ($persons) {
                foreach ($persons as $personData) {
                    $contact->persons()->create($personData);
                }
            }

            if ($addresses) {
                foreach ($addresses as $addressData) {
                    $contact->addresses()->create($addressData);
                }
            }

            return $contact->fresh(['persons', 'addresses']);
        });
    }

    /**
     * Update a contact with optional persons and addresses.
     */
    public function update(Contact $contact, array $data, ?array $persons = null, ?array $addresses = null): Contact
    {
        return DB::transaction(function () use ($contact, $data, $persons, $addresses) {
            $contact->update($data);

            if ($persons !== null) {
                $this->syncPersons($contact, $persons);
            }

            if ($addresses !== null) {
                $this->syncAddresses($contact, $addresses);
            }

            return $contact->fresh(['persons', 'addresses']);
        });
    }

    /**
     * Sync contact persons.
     */
    protected function syncPersons(Contact $contact, array $persons): void
    {
        $existingIds = collect($persons)->pluck('id')->filter()->toArray();
        
        // Delete removed persons
        $contact->persons()->whereNotIn('id', $existingIds)->delete();

        foreach ($persons as $personData) {
            if (isset($personData['id'])) {
                $contact->persons()->where('id', $personData['id'])->update($personData);
            } else {
                $contact->persons()->create($personData);
            }
        }
    }

    /**
     * Sync contact addresses.
     */
    protected function syncAddresses(Contact $contact, array $addresses): void
    {
        $existingIds = collect($addresses)->pluck('id')->filter()->toArray();
        
        // Delete removed addresses
        $contact->addresses()->whereNotIn('id', $existingIds)->delete();

        foreach ($addresses as $addressData) {
            if (isset($addressData['id'])) {
                $contact->addresses()->where('id', $addressData['id'])->update($addressData);
            } else {
                $contact->addresses()->create($addressData);
            }
        }
    }

    /**
     * Get customers with outstanding balances.
     */
    public function getCustomersWithBalances(Company $company): Collection
    {
        return Contact::query()
            ->where('company_id', $company->id)
            ->customers()
            ->enabled()
            ->withCount(['documents as invoice_count' => function ($query) {
                $query->where('type', 'invoice')
                    ->whereIn('status', ['draft', 'sent', 'partial']);
            }])
            ->get()
            ->map(function ($contact) {
                $contact->receivable_balance = $contact->getReceivableBalance();
                return $contact;
            })
            ->filter(fn ($c) => $c->receivable_balance->isPositive() || $c->invoice_count > 0);
    }

    /**
     * Get vendors with outstanding balances.
     */
    public function getVendorsWithBalances(Company $company): Collection
    {
        return Contact::query()
            ->where('company_id', $company->id)
            ->vendors()
            ->enabled()
            ->withCount(['documents as bill_count' => function ($query) {
                $query->where('type', 'bill')
                    ->whereIn('status', ['draft', 'sent', 'partial']);
            }])
            ->get()
            ->map(function ($contact) {
                $contact->payable_balance = $contact->getPayableBalance();
                return $contact;
            })
            ->filter(fn ($c) => $c->payable_balance->isPositive() || $c->bill_count > 0);
    }

    /**
     * Get contact statement (all transactions for a contact).
     */
    public function getStatement(Contact $contact, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = $contact->documents()
            ->whereIn('status', ['sent', 'partial', 'paid', 'overdue'])
            ->orderBy('issued_at');

        if ($startDate) {
            $query->where('issued_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('issued_at', '<=', $endDate);
        }

        $documents = $query->get();

        $openingBalance = Money::zero($contact->currency_code ?? 'KES');
        $runningBalance = $openingBalance;
        $transactions = [];

        foreach ($documents as $doc) {
            $amount = $doc->getTotal();
            $paid = $doc->getPaidAmount();
            $balance = $amount->subtract($paid);

            if ($doc->type === 'invoice' || $doc->type === 'debit_note') {
                $runningBalance = $runningBalance->add($balance);
            } else {
                $runningBalance = $runningBalance->subtract($balance);
            }

            $transactions[] = [
                'date' => $doc->issued_at,
                'document_number' => $doc->document_number,
                'type' => $doc->type,
                'amount' => $amount,
                'paid' => $paid,
                'balance' => $balance,
                'running_balance' => $runningBalance,
            ];
        }

        return [
            'contact' => $contact,
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'opening_balance' => $openingBalance,
            'transactions' => $transactions,
            'closing_balance' => $runningBalance,
        ];
    }

    /**
     * Merge two contacts into one.
     */
    public function merge(Contact $primary, Contact $secondary): Contact
    {
        return DB::transaction(function () use ($primary, $secondary) {
            // Move all documents to primary
            $secondary->documents()->update(['contact_id' => $primary->id]);

            // Move all persons to primary
            foreach ($secondary->persons as $person) {
                $person->update([
                    'contact_id' => $primary->id,
                    'is_primary' => false, // Primary contact's persons take precedence
                ]);
            }

            // Move all addresses to primary
            foreach ($secondary->addresses as $address) {
                $address->update([
                    'addressable_id' => $primary->id,
                ]);
            }

            // Soft delete secondary
            $secondary->delete();

            return $primary->fresh(['persons', 'addresses', 'documents']);
        });
    }

    /**
     * Get aging report for a contact.
     */
    public function getAging(Contact $contact): array
    {
        $documents = $contact->documents()
            ->whereIn('type', ['invoice', 'bill'])
            ->whereIn('status', ['sent', 'partial', 'overdue'])
            ->get();

        $aging = [
            'current' => Money::zero($contact->currency_code),
            '1-30' => Money::zero($contact->currency_code),
            '31-60' => Money::zero($contact->currency_code),
            '61-90' => Money::zero($contact->currency_code),
            'over_90' => Money::zero($contact->currency_code),
        ];

        $today = now();

        foreach ($documents as $doc) {
            $balance = $doc->getTotal()->subtract($doc->getPaidAmount());
            if ($balance->isZero()) {
                continue;
            }

            $dueDate = $doc->due_date;
            $daysOverdue = $dueDate->diffInDays($today, false);

            if ($daysOverdue <= 0) {
                $aging['current'] = $aging['current']->add($balance);
            } elseif ($daysOverdue <= 30) {
                $aging['1-30'] = $aging['1-30']->add($balance);
            } elseif ($daysOverdue <= 60) {
                $aging['31-60'] = $aging['31-60']->add($balance);
            } elseif ($daysOverdue <= 90) {
                $aging['61-90'] = $aging['61-90']->add($balance);
            } else {
                $aging['over_90'] = $aging['over_90']->add($balance);
            }
        }

        $total = Money::zero($contact->currency_code);
        foreach ($aging as $amount) {
            $total = $total->add($amount);
        }

        return [
            'contact' => $contact,
            'aging' => $aging,
            'total' => $total,
        ];
    }

    /**
     * Search contacts by name, email, or tax number.
     */
    public function search(Company $company, string $query, ?string $type = null): Collection
    {
        return Contact::query()
            ->where('company_id', $company->id)
            ->when($type, fn ($q) => $q->where('type', $type))
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('tax_number', 'like', "%{$query}%");
            })
            ->enabled()
            ->orderBy('name')
            ->limit(20)
            ->get();
    }
}
