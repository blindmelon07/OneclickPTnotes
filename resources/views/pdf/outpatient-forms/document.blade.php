<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ count($documents) === 1 ? \App\Models\OutpatientForm::documents()[$documents[0]] : 'Outpatient Forms' }} - {{ $form->patient_name }}</title>
    @include('pdf.notes.partials.styles')
    <style>
        @page { margin: 22px 30px; }
        body { font-size: 9.5px; }
        h2 { margin: 7px 0 3px; font-size: 10.5px; }
        .value { min-height: 18px; padding: 3px 5px; }
        .nowrap { white-space: nowrap; }
        .page-break { page-break-after: always; }
        table.title { width: 100%; border-collapse: collapse; border-bottom: 2px solid #000; margin-bottom: 8px; }
        table.title td { padding-bottom: 4px; font-weight: bold; }
        table.title .practice-name { font-size: 12px; }
        table.title .form-name { font-size: 14px; text-align: right; letter-spacing: 1px; }
        .fill { border-bottom: 1px solid #555; padding: 0 3px; min-width: 40px; display: inline-block; }
        .wide { min-width: 160px; }
        table.rows { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        table.rows td { padding: 2px 4px 2px 0; vertical-align: top; }
        table.rows .label { font-weight: bold; white-space: nowrap; width: 1%; }
        table.progress { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        table.progress th, table.progress td { border: 1px solid #999; padding: 2px 4px; text-align: left; vertical-align: top; }
        table.progress th { background: #f2f2f2; font-size: 9px; text-transform: uppercase; }
        table.columns { width: 100%; border-collapse: collapse; }
        table.columns > tbody > tr > td, table.columns > tr > td { vertical-align: top; padding-right: 10px; }
        .small { font-size: 9px; }
        table.sig-block { margin-top: 14px; }
        .owned { margin-top: 8px; padding-top: 6px; border-top: 1px solid #000; font-size: 8px; }
        .owned td { width: 33%; }
    </style>
</head>
<body>
    @foreach ($documents as $document)
        @include('pdf.outpatient-forms.partials.header', ['title' => \App\Models\OutpatientForm::documents()[$document]])
        @include('pdf.outpatient-forms.partials.'.$document)
        @include('pdf.outpatient-forms.partials.footer', ['signatureDate' => [
            \App\Models\OutpatientForm::DOCUMENT_INITIAL_EVALUATION => $data['ie']['date'],
            \App\Models\OutpatientForm::DOCUMENT_RE_EVALUATION => $data['re']['date'],
            \App\Models\OutpatientForm::DOCUMENT_DISCHARGE_SUMMARY => $data['dc']['date'],
        ][$document]])

        @unless ($loop->last)
            <div class="page-break"></div>
        @endunless
    @endforeach
</body>
</html>
