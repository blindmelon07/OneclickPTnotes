<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Records when the supervising therapist's stored signature was stamped
     * onto a note. Kept separate from `signed_at` (the author's own drawn
     * signature) so the audit trail can tell a stamp from a real signature.
     */
    public function up(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->timestamp('countersigned_at')->nullable()->after('patient_signed_at');
        });
    }

    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->dropColumn('countersigned_at');
        });
    }
};
