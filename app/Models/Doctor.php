<?php

namespace App\Models;

use Database\Factories\DoctorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['name', 'phone', 'fax', 'address', 'email'])]
class Doctor extends Model
{
    /** @use HasFactory<DoctorFactory> */
    use HasFactory, LogsActivity;

    /**
     * @return HasMany<Patient, $this>
     */
    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'phone', 'fax', 'address', 'email'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('doctor');
    }
}
