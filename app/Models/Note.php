<?php

namespace App\Models;

use Database\Factories\NoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['patient_id', 'visit_id', 'author_id', 'type', 'data'])]
class Note extends Model
{
    /** @use HasFactory<NoteFactory> */
    use HasFactory, LogsActivity;

    public const TYPE_IE = 'ie';

    public const TYPE_RE = 're';

    public const TYPE_DC = 'dc';

    public const TYPE_FU = 'fu';

    public const TYPE_ROUTE_SHEET = 'route_sheet';

    public const TYPE_VISIT_NOTE = 'visit_note';

    public const TYPE_MISSED_VISIT = 'missed_visit';

    public const TYPE_COMMUNICATION = 'communication';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
            'signed_at' => 'datetime',
            'patient_signed_at' => 'datetime',
            'countersigned_at' => 'datetime',
            'emailed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * @return BelongsTo<Visit, $this>
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function isSigned(): bool
    {
        return $this->signed_at !== null;
    }

    public function isPatientSigned(): bool
    {
        return $this->patient_signed_at !== null;
    }

    /**
     * Whether the supervising therapist's stored signature has been stamped
     * on this note. Distinct from `isSigned()`, which is the author's own.
     */
    public function isCountersigned(): bool
    {
        return $this->countersigned_at !== null;
    }

    /**
     * The signature on file for the supervising therapist, if one is set up.
     */
    public static function countersignaturePath(): ?string
    {
        $path = config('notes.letterhead.supervising_therapist_signature');

        return $path && file_exists(public_path($path)) ? public_path($path) : null;
    }

    public function isEmailed(): bool
    {
        return $this->emailed_at !== null;
    }

    public function label(): string
    {
        return match ($this->type) {
            self::TYPE_IE => 'Initial Evaluation',
            self::TYPE_RE => 'Re-Evaluation',
            self::TYPE_DC => 'Discharge',
            self::TYPE_FU => 'Follow-Up Visit',
            self::TYPE_ROUTE_SHEET => 'PT Route Sheet',
            self::TYPE_VISIT_NOTE => 'Daily Visit Note',
            self::TYPE_MISSED_VISIT => 'Missed Visit Report',
            self::TYPE_COMMUNICATION => 'Communication Note',
            default => ucfirst((string) $this->type),
        };
    }

    /**
     * The display label for a type, without needing a persisted note.
     */
    public static function labelFor(string $type): string
    {
        return (new self(['type' => $type]))->label();
    }

    /**
     * @return array<int, string>
     */
    public static function types(): array
    {
        return [self::TYPE_IE, self::TYPE_RE, self::TYPE_DC, self::TYPE_FU];
    }

    /**
     * The per-visit paperwork the PTA completes from the visit wizard, in the
     * order the steps run. `types()` deliberately stays the clinical IE/RE/DC/FU
     * set — those drive the standalone note form and the billing rates.
     *
     * @return array<int, string>
     */
    public static function wizardTypes(): array
    {
        return [self::TYPE_ROUTE_SHEET, self::TYPE_VISIT_NOTE, self::TYPE_MISSED_VISIT, self::TYPE_COMMUNICATION];
    }

    /**
     * Wizard steps the PTA may skip.
     *
     * @return array<int, string>
     */
    public static function optionalWizardTypes(): array
    {
        return [self::TYPE_MISSED_VISIT, self::TYPE_COMMUNICATION];
    }

    /**
     * Route sheet billing codes, keyed by the code written on the sheet.
     *
     * @return array<string, string>
     */
    public static function visitCodes(): array
    {
        return [
            'G0-151' => 'Services performed by qualified PT in the home setting or hospice setting',
            'G0-152' => 'Services performed by qualified OT in the home setting or hospice setting',
            'G0-157' => 'Services performed by qualified PTA in the home setting or hospice setting',
            'G0-158' => 'Services performed by qualified COTA in the home setting or hospice setting',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function homeboundStatuses(): array
    {
        return [
            'Needs assistance for all activities',
            'Requires assistance to ambulate',
            'Residual weakness',
            'Unable to safely leave home unassisted',
            'Dependent upon assistive device/s',
            'Confusion, unable to go out of home alone',
            'Severe SOB, SOB on exertion, wheezing',
            'Bed bound',
            'Pain limits mobility',
            'Poor balance / coordination',
            'Requires holding onto walls/furnitures',
            'Wheelchair bound, cannot propel',
            'Mental status precludes leaving home',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function servicesProvided(): array
    {
        return [
            'Initial evaluation',
            'Discharge evaluation',
            'Therapeutic exercises (stretch / strength)',
            'Therapeutic activities (functional)',
            'Gait training',
            'Manual therapy',
            'Neuromuscular reeducation',
            'Balance training (sitting / standing)',
            'Prosthetic training',
            'ADL and self care training',
            'Teach patient fall precaution',
            'Teach wheelchair skills',
            'Teach bed mobility skills',
            'Teach hip safety precautions',
            'Teach use of assistive device',
            'Teach home exercise program (HEP)',
            'Ultrasound',
            'Iontophoresis',
            'UVR / IRR',
            'Electrotherapy',
            'TENS',
            'CPM',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function missedVisitReasons(): array
    {
        return [
            'MD appointment',
            'Patient hospitalized',
            'Patient / family decision',
            'No answer to locked door',
            'Unable to contact patient',
            'Patient taken out of town by family',
            'Family available to assist with care',
            'Inclement weather',
        ];
    }

    /**
     * The default clinical documentation fields shared by all note types.
     *
     * @return array<string, string>
     */
    public static function dataFieldLabels(): array
    {
        return [
            'subjective' => 'Subjective / Chief Complaint',
            'blood_pressure' => 'Blood Pressure',
            'heart_rate' => 'Heart Rate',
            'pain_level' => 'Pain Level (0-10)',
            'objective_findings' => 'Objective Findings',
            'assessment' => 'Assessment',
            'goals' => 'Goals',
            'plan' => 'Plan',
            'visit_duration_minutes' => 'Visit Duration (minutes)',
            'therapist_notes' => 'Additional Therapist Notes',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['patient_id', 'visit_id', 'author_id', 'type', 'data', 'signed_at', 'patient_signed_at', 'countersigned_at', 'pdf_path', 'emailed_to', 'emailed_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('note');
    }
}
