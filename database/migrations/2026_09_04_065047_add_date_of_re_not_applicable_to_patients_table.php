<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            // Marks a patient who never gets a re-evaluation, so an empty
            // `date_of_re` reads as "not applicable" rather than "not yet done".
            $table->boolean('date_of_re_not_applicable')->default(false)->after('date_of_re');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn('date_of_re_not_applicable');
        });
    }
};
