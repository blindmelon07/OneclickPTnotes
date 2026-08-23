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
            default => ucfirst((string) $this->type),
        };
    }

    /**
     * @return array<int, string>
     */
    public static function types(): array
    {
        return [self::TYPE_IE, self::TYPE_RE, self::TYPE_DC, self::TYPE_FU];
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
            ->logOnly(['patient_id', 'visit_id', 'author_id', 'type', 'data', 'signed_at', 'pdf_path', 'emailed_to', 'emailed_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('note');
    }
}
