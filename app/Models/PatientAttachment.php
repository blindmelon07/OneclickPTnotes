<?php

namespace App\Models;

use Database\Factories\PatientAttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['patient_id', 'uploaded_by', 'category', 'original_filename', 'path'])]
class PatientAttachment extends Model
{
    /** @use HasFactory<PatientAttachmentFactory> */
    use HasFactory, LogsActivity;

    public const CATEGORY_REFERRAL = 'referral';

    public const CATEGORY_PRESCRIPTION = 'prescription';

    public const CATEGORY_IMAGING = 'imaging';

    public const CATEGORY_OTHER = 'other';

    /**
     * @return array<string, string>
     */
    public static function categoryLabels(): array
    {
        return [
            self::CATEGORY_REFERRAL => 'Referral',
            self::CATEGORY_PRESCRIPTION => 'Prescription',
            self::CATEGORY_IMAGING => 'Imaging / Report',
            self::CATEGORY_OTHER => 'Other',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function categories(): array
    {
        return array_keys(self::categoryLabels());
    }

    public function categoryLabel(): string
    {
        return self::categoryLabels()[$this->category] ?? ucfirst($this->category);
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['patient_id', 'uploaded_by', 'category', 'original_filename'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('patient');
    }
}
