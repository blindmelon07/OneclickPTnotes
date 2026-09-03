<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Office Notification Email
    |--------------------------------------------------------------------------
    |
    | Signed clinical notes are emailed to this address once a staff member
    | completes and signs a note.
    |
    */

    'office_email' => env('NOTES_OFFICE_EMAIL', 'office@example.com'),

    /*
    |--------------------------------------------------------------------------
    | Printed Letterhead
    |--------------------------------------------------------------------------
    |
    | Printed at the foot of every generated form PDF, and on the signature
    | block above it. `supervising_therapist` is the PT who countersigns the
    | PTA's paperwork — the PTA's own name comes from the note's author.
    |
    */

    'letterhead' => [
        'practice' => env('NOTES_PRACTICE', 'PT-RN CARE, INC.'),
        'tagline' => env('NOTES_TAGLINE', 'PHYSICAL THERAPY SERVICES'),
        'address' => env('NOTES_ADDRESS', '1663 Beverly Blvd, Suite 202, Los Angeles, CA, 90026'),
        'email' => env('NOTES_EMAIL', 'ptrncareinc@sbcglobal.net'),
        'phone' => env('NOTES_PHONE', '213.250.0078, 213.250.9978'),
        'fax' => env('NOTES_FAX', '213.250.5578'),
        'website' => env('NOTES_WEBSITE', 'www.ptrncareinc.com'),
        'supervising_therapist' => env('NOTES_SUPERVISING_THERAPIST', 'HIRAM OWEN BRIAN MASAYON, PT, CCI'),
        'supervising_therapist_title' => env('NOTES_SUPERVISING_TITLE', 'Registered Physical Therapist'),
        'assistant_title' => env('NOTES_ASSISTANT_TITLE', 'Physical Therapist Assistant'),

        /*
         * The supervising therapist's signature on file, stamped onto forms
         * automatically once the PT Assistant signs them. A path relative to
         * public/ (e.g. 'signatures/supervising-therapist.png'); leave null
         * and the therapist's line prints blank for wet signing instead.
         */
        'supervising_therapist_signature' => env('NOTES_SUPERVISING_SIGNATURE'),

        /*
         * Optional practice logo printed between the address and contact
         * columns. A path relative to public/ (e.g. 'pt-rn-care-logo.png');
         * leave null and the footer simply prints without it.
         */
        'logo_path' => env('NOTES_LOGO_PATH'),
    ],

];
