<?php

use App\Models\Invoice;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('invoices', 'pages::invoices.index')->name('invoices.index');
    Route::livewire('invoices/create', 'pages::invoices.create')->name('invoices.create');
    Route::livewire('invoices/{invoice}', 'pages::invoices.show')->name('invoices.show');

    Route::get('invoices/{invoice}/download', function (Invoice $invoice) {
        abort_unless(auth()->user()->can('invoices.manage'), 403);
        abort_unless($invoice->pdf_path && Storage::disk('local')->exists($invoice->pdf_path), 404);

        return Storage::disk('local')->download(
            $invoice->pdf_path,
            sprintf('%s.pdf', $invoice->invoice_number)
        );
    })->name('invoices.download');
});
