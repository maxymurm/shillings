<?php

namespace Tests\Unit\Models;

use App\Models\Contact;
use App\Models\ContactPerson;
use App\Models\Company;
use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_contact(): void
    {
        $contact = Contact::factory()->create([
            'name' => 'Test Company Ltd',
            'type' => 'customer',
        ]);

        $this->assertDatabaseHas('contacts', [
            'name' => 'Test Company Ltd',
            'type' => 'customer',
        ]);
    }

    public function test_it_has_type_scopes(): void
    {
        Contact::factory()->customer()->create();
        Contact::factory()->vendor()->create();
        Contact::factory()->employee()->create();

        $this->assertCount(1, Contact::customers()->get());
        $this->assertCount(1, Contact::vendors()->get());
        $this->assertCount(1, Contact::employees()->get());
    }

    public function test_it_has_enabled_scope(): void
    {
        Contact::factory()->create(['enabled' => true]);
        Contact::factory()->create(['enabled' => false]);

        $this->assertCount(1, Contact::enabled()->get());
    }

    public function test_it_belongs_to_company(): void
    {
        $company = Company::factory()->create();
        $contact = Contact::factory()->create(['company_id' => $company->id]);

        $this->assertTrue($contact->company->is($company));
    }

    public function test_it_has_persons(): void
    {
        $contact = Contact::factory()->create();
        
        ContactPerson::create([
            'contact_id' => $contact->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'is_primary' => true,
        ]);

        $this->assertCount(1, $contact->fresh()->persons);
    }

    public function test_it_has_documents(): void
    {
        $contact = Contact::factory()->create();
        Document::factory()->create(['contact_id' => $contact->id]);

        $this->assertCount(1, $contact->fresh()->documents);
    }

    public function test_it_has_primary_contact(): void
    {
        $contact = Contact::factory()->create();
        
        $person = ContactPerson::create([
            'contact_id' => $contact->id,
            'name' => 'John Doe',
            'is_primary' => true,
        ]);

        $this->assertTrue($contact->fresh()->primaryPerson->is($person));
    }
}
