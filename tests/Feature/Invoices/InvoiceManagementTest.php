<?php

use App\Models\HomeHealthAgency;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Note;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

const WEEK_START = '2026-08-17'; // a Monday
const WEEK_MID = '2026-08-19'; // Wednesday of the same week

beforeEach(function () {
    Storage::fake('local');
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('non-permitted users are forbidden from the invoices pages', function () {
    $user = User::factory()->create();
    $invoice = Invoice::factory()->create();

    $this->actingAs($user)->get(route('invoices.index'))->assertForbidden();
    $this->actingAs($user)->get(route('invoices.create'))->assertForbidden();
    $this->actingAs($user)->get(route('invoices.show', $invoice))->assertForbidden();
});

test('a pt assistant can build a weekly invoice from their own unbilled visits', function () {
    $ptAssistant = User::factory()->create()->assignRole('PT Assistant');
    $agency = HomeHealthAgency::factory()->create();
    $patient = Patient::factory()->create(['home_health_agency_id' => $agency->id]);

    $ieVisit = Visit::factory()->create([
        'patient_id' => $patient->id,
        'therapist_id' => $ptAssistant->id,
        'visit_type' => Note::TYPE_IE,
        'scheduled_at' => WEEK_MID,
    ]);

    $fuVisit = Visit::factory()->create([
        'patient_id' => $patient->id,
        'therapist_id' => $ptAssistant->id,
        'visit_type' => Note::TYPE_FU,
        'scheduled_at' => WEEK_MID,
    ]);

    // A visit outside the week and one belonging to a different therapist should not be selectable.
    Visit::factory()->create([
        'patient_id' => $patient->id,
        'therapist_id' => $ptAssistant->id,
        'visit_type' => Note::TYPE_FU,
        'scheduled_at' => '2026-08-10',
    ]);
    Visit::factory()->create([
        'patient_id' => $patient->id,
        'therapist_id' => User::factory(),
        'visit_type' => Note::TYPE_FU,
        'scheduled_at' => WEEK_MID,
    ]);

    $component = Livewire::actingAs($ptAssistant)
        ->test('pages::invoices.create')
        ->set('home_health_agency_id', $agency->id)
        ->set('weekOf', WEEK_START);

    expect($component->get('eligibleVisits'))->toHaveCount(2);

    $component->set('selectedVisitIds', [$ieVisit->id, $fuVisit->id])
        ->call('createInvoice')
        ->assertHasNoErrors();

    $invoice = Invoice::sole();

    expect($invoice->home_health_agency_id)->toBe($agency->id);
    expect($invoice->created_by)->toBe($ptAssistant->id);
    expect($invoice->status)->toBe(Invoice::STATUS_DRAFT);
    expect((float) $invoice->total_amount)->toBe(
        config('billing.visit_rates')[Note::TYPE_IE] + config('billing.visit_rates')[Note::TYPE_FU]
    );
    expect($invoice->items)->toHaveCount(2);
    expect($invoice->invoice_number)->not->toBe('PENDING');

    Storage::disk('local')->assertExists($invoice->pdf_path);
});

test('a visit already on an invoice is not eligible for another invoice', function () {
    $ptAssistant = User::factory()->create()->assignRole('PT Assistant');
    $agency = HomeHealthAgency::factory()->create();
    $patient = Patient::factory()->create(['home_health_agency_id' => $agency->id]);

    $visit = Visit::factory()->create([
        'patient_id' => $patient->id,
        'therapist_id' => $ptAssistant->id,
        'visit_type' => Note::TYPE_FU,
        'scheduled_at' => WEEK_MID,
    ]);

    $invoice = Invoice::factory()->create(['home_health_agency_id' => $agency->id]);
    InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'visit_id' => $visit->id]);

    $component = Livewire::actingAs($ptAssistant)
        ->test('pages::invoices.create')
        ->set('home_health_agency_id', $agency->id)
        ->set('weekOf', WEEK_START);

    expect($component->get('eligibleVisits'))->toHaveCount(0);
});

test('an invoice can be marked sent then paid, gated by invoices.manage', function () {
    $ptAssistant = User::factory()->create()->assignRole('PT Assistant');
    $invoice = Invoice::factory()->create();

    Livewire::actingAs($ptAssistant)
        ->test('pages::invoices.show', ['invoice' => $invoice])
        ->call('markAsSent');

    expect($invoice->fresh()->status)->toBe(Invoice::STATUS_SENT);
    expect($invoice->fresh()->sent_at)->not->toBeNull();

    Livewire::actingAs($ptAssistant)
        ->test('pages::invoices.show', ['invoice' => $invoice])
        ->call('markAsPaid');

    expect($invoice->fresh()->status)->toBe(Invoice::STATUS_PAID);
    expect($invoice->fresh()->paid_at)->not->toBeNull();
});

test('only a draft invoice can be deleted, freeing its visit', function () {
    $ptAssistant = User::factory()->create()->assignRole('PT Assistant');
    $visit = Visit::factory()->create();
    $invoice = Invoice::factory()->create();
    InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'visit_id' => $visit->id]);

    $sentInvoice = Invoice::factory()->sent()->create();

    Livewire::actingAs($ptAssistant)
        ->test('pages::invoices.show', ['invoice' => $sentInvoice])
        ->call('delete');

    expect(Invoice::find($sentInvoice->id))->not->toBeNull();

    Livewire::actingAs($ptAssistant)
        ->test('pages::invoices.show', ['invoice' => $invoice])
        ->call('delete');

    expect(Invoice::find($invoice->id))->toBeNull();
    expect($visit->fresh()->invoiceItem)->toBeNull();
});
