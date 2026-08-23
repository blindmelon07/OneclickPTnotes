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
        Schema::create('patients', function (Blueprint $table) {
            $table->id();

            // Demographics
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('diagnosis')->nullable();

            // Relationships
            $table->foreignId('doctor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('insurance_company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('home_health_agency_id')->nullable()->constrained()->nullOnDelete();

            // Treatment info
            $table->unsignedInteger('approved_visits')->nullable();
            $table->string('cert_period')->nullable();
            $table->date('date_referred')->nullable();
            $table->date('date_of_ie')->nullable();
            $table->date('date_of_re')->nullable();
            $table->date('date_of_dc')->nullable();
            $table->string('pt_freq')->nullable();
            $table->unsignedInteger('pta_visits')->nullable();

            // Roster status: active (green) / discharged (red) / hospitalized (yellow)
            $table->string('status')->default('active');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
