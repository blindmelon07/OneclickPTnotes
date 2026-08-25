---
paths:
  - 'app/Models/{Invoice,InvoiceItem}.php,app/Actions/Invoices/**,resources/views/pages/invoices/**,config/billing.php'
---

# Invoices

## Weekly invoicing domain
Invoice belongsTo HomeHealthAgency + User (`created_by`); hasMany InvoiceItem, each belongsTo one Visit (unique FK — a Visit can only ever be on one InvoiceItem, enforced by a DB unique index and `Visit::invoiceItem()` HasOne). Rate per visit_type (shares Note::TYPE_* keys) lives in `config/billing.php`, env-overridable, snapshotted onto `invoice_items.rate_amount` at creation time so later rate changes don't retroactively alter old invoices.

`pages::invoices.create` computes the Mon–Sun week from a picked date and only offers the *logged-in user's own* (`therapist_id = auth()->id()`) unbilled visits for the chosen HomeHealthAgency's patients — see the `eligibleVisits()` query pattern.

Unlike Patient/Note (viewable by any authenticated user, see [[patient-note-clinical-documentation-workflow]]), Invoice viewing itself is gated by `invoices.manage` (financial data) — every invoices Livewire page mounts with `abort_unless(auth()->user()->can('invoices.manage'), 403)`. PDF generated via `App\Actions\Invoices\GenerateInvoicePdf` (same dompdf pattern as `GenerateNotePdf`). Roles granted `invoices.manage`: admin, Biller, Office Manager, PT Assistant — see [[rbac-via-spatie-laravel-permission]].
