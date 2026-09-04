<?php

namespace App\Models;

use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
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
    'pt_assistant_id',
    'approved_visits',
    'cert_period',
    'date_referred',
    'date_of_ie',
    'date_of_re',
    'date_of_re_not_applicable',
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

    public const STATUS_ON_HOLD = 'on_hold';

    public const STATUS_PATIENT_REFUSED = 'patient_refused';

    public const STATUS_PASSED_AWAY = 'passed_away';

    public const STATUS_STOP_ORDERED = 'stop_ordered';

    public const STATUS_HAVING_SURGERY = 'having_surgery';

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
            'date_of_re_not_applicable' => 'boolean',
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
     * @return BelongsTo<User, $this>
     */
    public function ptAssistant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pt_assistant_id');
    }

    /**
     * Limit the roster to what the given user is allowed to see — their own
     * caseload for a PT Assistant, everything for everyone else.
     *
     * @param  Builder<Patient>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        $query->when(
            $user->isRestrictedToAssignedPatients(),
            fn (Builder $query) => $query->where('pt_assistant_id', $user->id),
        );
    }

    /**
     * Whether the given user is allowed to open this patient's chart.
     */
    public function isVisibleTo(User $user): bool
    {
        return ! $user->isRestrictedToAssignedPatients()
            || $this->pt_assistant_id === $user->id;
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
     * Where the next scheduled visit falls in this patient's course of care.
     */
    public function nextVisitNumber(): int
    {
        return $this->visits()->count() + 1;
    }

    /**
     * How many visits of a course the supervising admin performs personally —
     * the first (evaluation) and the last (discharge). See
     * `therapistForVisitNumber()`, which is what actually assigns them.
     */
    public const ADMIN_VISITS_PER_COURSE = 2;

    /**
     * The PT Assistant's share of an approved course: everything the admin does
     * not take off the top. A course of 7 leaves the assistant 5.
     */
    public static function ptaVisitsForApprovedVisits(?int $approvedVisits): ?int
    {
        return $approvedVisits === null
            ? null
            : max(0, $approvedVisits - self::ADMIN_VISITS_PER_COURSE);
    }

    /**
     * Who performs a given visit: the admin takes the first and the last visit
     * of the course personally, the assigned PT Assistant takes everything in
     * between. `approved_visits` is what makes a visit "the last" one, so with
     * no count set only the first visit falls to the admin.
     */
    public function therapistForVisitNumber(int $sequence): ?User
    {
        $isFirstVisit = $sequence === 1;
        $isFinalVisit = $this->approved_visits !== null && $sequence === (int) $this->approved_visits;

        return $isFirstVisit || $isFinalVisit
            ? User::supervisingAdmin()
            : $this->ptAssistant;
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
            self::STATUS_ON_HOLD => 'amber',
            self::STATUS_PATIENT_REFUSED => 'orange',
            self::STATUS_PASSED_AWAY => 'zinc',
            self::STATUS_STOP_ORDERED => 'rose',
            self::STATUS_HAVING_SURGERY => 'blue',
            default => 'zinc',
        };
    }

    /**
     * The human-readable label for this patient's status.
     */
    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Every selectable roster status, keyed by stored value.
     *
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_DISCHARGED => 'Discharged',
            self::STATUS_HOSPITALIZED => 'Hospitalized',
            self::STATUS_ON_HOLD => 'On-Hold',
            self::STATUS_PATIENT_REFUSED => 'Patient Refused HHPT',
            self::STATUS_PASSED_AWAY => 'Px Passed Away',
            self::STATUS_STOP_ORDERED => 'HHA/MD ordered to stop HHPT',
            self::STATUS_HAVING_SURGERY => 'Having surgery',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function statuses(): array
    {
        return array_keys(self::statusLabels());
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 'address', 'phone', 'diagnosis', 'doctor_id', 'insurance_company_id',
                'home_health_agency_id', 'pt_assistant_id', 'approved_visits', 'cert_period', 'date_referred',
                'date_of_ie', 'date_of_re', 'date_of_re_not_applicable', 'date_of_dc', 'pt_freq', 'pta_visits', 'status',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('patient');
    }
}
