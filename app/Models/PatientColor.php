<?php

namespace App\Models;

use Database\Factories\PatientColorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A viewer's personal color choice for one patient, used to color-code that
 * patient's visits on their own Calendar page. Purely a per-user UI
 * preference — not a clinical/business record, so it isn't activity-logged.
 */
#[Fillable(['user_id', 'patient_id', 'color'])]
class PatientColor extends Model
{
    /** @use HasFactory<PatientColorFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
