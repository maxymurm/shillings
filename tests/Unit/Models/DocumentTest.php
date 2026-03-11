<?php

namespace Tests\Unit\Models;

use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\Contact;
use App\Models\Company;
use App\Models\Account;
use App\Models\AccountType;
use App\ValueObjects\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_document(): void
    {
        $document = Document::factory()->invoice()->create([
            'document_number' => 'INV-2026-000001',
        ]);

        $this->assertDatabaseHas('documents', [
            'document_number' => 'INV-2026-000001',
            'type' => 'invoice',
        ]);
    }

    public function test_it_has_type_scopes(): void
    {
        Document::factory()->invoice()->create();
        Document::factory()->bill()->create();
        Document::factory()->quote()->create();

        $this->assertCount(1, Document::invoices()->get());
        $this->assertCount(1, Document::bills()->get());
        $this->assertCount(1, Document::quotes()->get());
    }

    public function test_it_belongs_to_contact(): void
    {
        $contact = Contact::factory()->create();
        $document = Document::factory()->create(['contact_id' => $contact->id]);

        $this->assertTrue($document->contact->is($contact));
    }

    public function test_it_calculates_totals(): void
    {
        $document = Document::factory()->create([
            'subtotal_num' => 100000, // 1000.00
            'subtotal_denom' => 100,
            'tax_total_num' => 16000,  // 160.00
            'tax_total_denom' => 100,
            'total_num' => 116000,     // 1160.00
            'total_denom' => 100,
        ]);

        $total = $document->getTotal();
        
        $this->assertEquals(116000, $total->getNumerator());
        $this->assertEquals(100, $total->getDenominator());
    }

    public function test_it_tracks_paid_amount(): void
    {
        $document = Document::factory()->create([
            'total_num' => 100000,
            'total_denom' => 100,
            'amount_paid_num' => 50000,
            'amount_paid_denom' => 100,
        ]);

        $paid = $document->getPaidAmount();
        
        $this->assertEquals(50000, $paid->getNumerator());
    }

    public function test_draft_document_is_editable(): void
    {
        $document = Document::factory()->create(['status' => 'draft']);
        
        $this->assertTrue($document->isEditable());
    }

    public function test_paid_document_is_not_editable(): void
    {
        $document = Document::factory()->paid()->create();
        
        $this->assertFalse($document->isEditable());
    }

    public function test_it_can_mark_as_sent(): void
    {
        $document = Document::factory()->create(['status' => 'draft']);
        $document->markAsSent();
        
        $this->assertEquals('sent', $document->fresh()->status);
        $this->assertNotNull($document->fresh()->sent_at);
    }

    public function test_it_can_record_payment(): void
    {
        $document = Document::factory()->sent()->create([
            'total_num' => 100000,
            'total_denom' => 100,
        ]);
        
        $payment = Money::fromDecimal(500.00, 'KES');
        $document->recordPayment($payment);
        
        $this->assertEquals('partial', $document->fresh()->status);
        $this->assertEquals(50000, $document->fresh()->amount_paid_num);
    }

    public function test_full_payment_marks_as_paid(): void
    {
        $document = Document::factory()->sent()->create([
            'total_num' => 100000,
            'total_denom' => 100,
        ]);
        
        $payment = Money::fromDecimal(1000.00, 'KES');
        $document->recordPayment($payment);
        
        $this->assertEquals('paid', $document->fresh()->status);
    }

    public function test_it_can_be_cancelled(): void
    {
        $document = Document::factory()->create(['status' => 'draft']);
        $document->cancel('Customer requested cancellation');
        
        $this->assertEquals('cancelled', $document->fresh()->status);
        $this->assertStringContainsString('Customer requested', $document->fresh()->notes);
    }
}
