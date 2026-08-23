<?php

namespace App\Models;

use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'name',
    'address',
    'phone',
    'diagnosis',
    'doctor_id',
    'insurance_company_id',
    'home_health_agency_id',
    'approved_visits',
    'cert_period',
    'date_referred',
    'date_of_ie',
    'date_of_re',
    'date_of_dc',
    'pt_freq',
    'pta_visits',
    'status',
])]
class Patient extends Model
{
    /** @use HasFactory<PatientFactory> */
    use HasFactory, LogsActivity;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_DISCHARGED = 'discharged';

    public const STATUS_HOSPITALIZED = 'hospitalized';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_referred' => 'date',
            'date_of_ie' => 'date',
            'date_of_re' => 'date',
            'date_of_dc' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Doctor, $this>
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * @return BelongsTo<InsuranceCompany, $this>
     */
    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    /**
     * @return BelongsTo<HomeHealthAgency, $this>
     */
    public function homeHealthAgency(): BelongsTo
    {
        return $this->belongsTo(HomeHealthAgency::class);
    }

    /**
     * @return HasMany<Visit, $this>
     */
    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    /**
     * @return HasMany<Note, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    /**
     * The roster highlight color for this patient's status.
     */
    public function statusColor(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'green',
            self::STATUS_DISCHARGED => 'red',
            self::STATUS_HOSPITALIZED => 'yellow',
            default => 'zinc',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function statuses(): array
    {
        return [self::STATUS_ACTIVE, self::STATUS_DISCHARGED, self::STATUS_HOSPITALIZED];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 'address', 'phone', 'diagnosis', 'doctor_id', 'insurance_company_id',
                'home_health_agency_id', 'approved_visits', 'cert_period', 'date_referred',
                'date_of_ie', 'date_of_re', 'date_of_dc', 'pt_freq', 'pta_visits', 'status',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('patient');
    }
}
