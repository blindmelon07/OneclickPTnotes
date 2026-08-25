<?php

use App\Models\Note;

return [

    /*
    |--------------------------------------------------------------------------
    | Weekly Invoice Visit Rates
    |--------------------------------------------------------------------------
    |
    | Flat dollar amount billed per visit type when a PT Assistant generates
    | a weekly invoice. These are placeholder defaults — tune them via the
    | matching env vars, no code change needed.
    |
    */

    'visit_rates' => [
        Note::TYPE_IE => (float) env('BILLING_RATE_IE', 100),
        Note::TYPE_RE => (float) env('BILLING_RATE_RE', 85),
        Note::TYPE_DC => (float) env('BILLING_RATE_DC', 90),
        Note::TYPE_FU => (float) env('BILLING_RATE_FU', 65),
    ],

];
