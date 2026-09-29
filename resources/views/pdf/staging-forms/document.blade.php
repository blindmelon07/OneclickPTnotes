<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ count($documents) === 1 ? \App\Models\StagingForm::documents()[$documents[0]] : 'Staging Forms' }} — {{ $form->patient_name }}</title>
    @include('pdf.notes.partials.styles')
    <style>
        .page-break { page-break-after: always; }
        .fill { border-bottom: 1px solid #555; padding: 0 3px; min-width: 60px; display: inline-block; }
        table.levels { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        table.levels td { padding: 2px 4px 2px 0; vertical-align: top; }
        table.levels .group { font-weight: bold; width: 110px; }
        .legend { font-size: 8px; color: #444; margin-bottom: 4px; }
        .checks.three td { width: 33%; }
        .small { font-size: 9px; }
        ol.list { margin: 2px 0 8px 18px; padding: 0; }
    </style>
</head>
<body>
    @foreach ($documents as $document)
        @include('pdf.staging-forms.partials.'.$document)
        @include('pdf.staging-forms.partials.footer', ['signatureDate' => $data['evaluation_date']])

        @unless ($loop->last)
            <div class="page-break"></div>
        @endunless
    @endforeach
</body>
</html>
