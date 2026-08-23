<?php

namespace App\Models;

use Database\Factories\HomeHealthAgencyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['name', 'phone', 'fax', 'address', 'referred_by'])]
class HomeHealthAgency extends Model
{
    /** @use HasFactory<HomeHealthAgencyFactory> */
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
            ->logOnly(['name', 'phone', 'fax', 'address', 'referred_by'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('home_health_agency');
    }
}
