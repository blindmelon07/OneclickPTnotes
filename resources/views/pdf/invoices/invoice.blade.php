<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }} — {{ $invoice->homeHealthAgency->name }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 18px; margin-bottom: 0; }
        .subtitle { color: #555; margin-top: 2px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        table.meta td { padding: 4px 6px; border: 1px solid #ddd; vertical-align: top; }
        table.meta td.label { width: 160px; font-weight: bold; background: #f5f5f5; }
        table.items th, table.items td { padding: 6px 8px; border: 1px solid #ddd; text-align: left; }
        table.items th { background: #f5f5f5; }
        table.items td.amount, table.items th.amount { text-align: right; }
        .total-row td { font-weight: bold; }
    </style>
</head>
<body>
    <h1>{{ config('app.name') }}</h1>
    <p class="subtitle">Invoice {{ $invoice->invoice_number }}</p>

    <table class="meta">
        <tr>
            <td class="label">Bill to</td>
            <td>{{ $invoice->homeHealthAgency->name }}</td>
            <td class="label">Status</td>
            <td>{{ ucfirst($invoice->status) }}</td>
        </tr>
        <tr>
            <td class="label">Week</td>
            <td>{{ $invoice->week_start_date->format('M j, Y') }} – {{ $invoice->week_end_date->format('M j, Y') }}</td>
            <td class="label">Prepared by</td>
            <td>{{ $invoice->createdBy?->name }}</td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Patient</th>
                <th>Visit type</th>
                <th>Visit date</th>
                <th class="amount">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td>{{ $item->visit->patient->name }}</td>
                    <td>{{ strtoupper($item->visit->visit_type) }}</td>
                    <td>{{ $item->visit->scheduled_at->format('M j, Y') }}</td>
                    <td class="amount">${{ number_format((float) $item->rate_amount, 2) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3">Total</td>
                <td class="amount">${{ number_format((float) $invoice->total_amount, 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
