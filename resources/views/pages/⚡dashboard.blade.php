<?php

use App\Models\Doctor;
use App\Models\HomeHealthAgency;
use App\Models\InsuranceCompany;
use App\Models\Note;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;

new #[Title('Dashboard')] class extends Component
{
    /**
     * @return array<string, int>
     */
    #[Computed]
    public function patientStats(): array
    {
        return [
            'total' => Patient::count(),
            'active' => Patient::where('status', Patient::STATUS_ACTIVE)->count(),
            'discharged' => Patient::where('status', Patient::STATUS_DISCHARGED)->count(),
            'hospitalized' => Patient::where('status', Patient::STATUS_HOSPITALIZED)->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function myStats(): array
    {
        $userId = auth()->id();

        return [
            'visits_today' => Visit::where('therapist_id', $userId)->whereDate('scheduled_at', today())->count(),
            'upcoming_visits' => Visit::where('therapist_id', $userId)->where('scheduled_at', '>=', now())->count(),
            'notes_signed_this_week' => Note::where('author_id', $userId)->where('signed_at', '>=', now()->startOfWeek())->count(),
            'unsigned_drafts' => Note::where('author_id', $userId)->whereNull('signed_at')->count(),
        ];
    }

    /**
     * @return array<string, int>|null
     */
    #[Computed]
    public function adminStats(): ?array
    {
        if (! auth()->user()->can('users.view')) {
            return null;
        }

        return [
            'users' => User::count(),
            'doctors' => Doctor::count(),
            'agencies' => HomeHealthAgency::count(),
            'insurers' => InsuranceCompany::count(),
            'activity_today' => Activity::whereDate('created_at', today())->count(),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-8 rounded-xl">
    <div>
        <flux:heading size="lg" class="mb-3">{{ __('Patients') }}</flux:heading>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-dashboard-stat icon="users" :label="__('Total patients')" :value="$this->patientStats['total']" :href="route('patients.index')" />
            <x-dashboard-stat icon="check-circle" color="green" :label="__('Active')" :value="$this->patientStats['active']" :href="route('patients.index')" />
            <x-dashboard-stat icon="x-circle" color="red" :label="__('Discharged')" :value="$this->patientStats['discharged']" :href="route('patients.index')" />
            <x-dashboard-stat icon="exclamation-triangle" color="amber" :label="__('Hospitalized')" :value="$this->patientStats['hospitalized']" :href="route('patients.index')" />
        </div>
    </div>

    <div>
        <flux:heading size="lg" class="mb-3">{{ __('My activity') }}</flux:heading>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-dashboard-stat icon="calendar-days" color="blue" :label="__('My visits today')" :value="$this->myStats['visits_today']" />
            <x-dashboard-stat icon="calendar" color="blue" :label="__('My upcoming visits')" :value="$this->myStats['upcoming_visits']" />
            <x-dashboard-stat icon="pencil-square" color="green" :label="__('Notes I signed this week')" :value="$this->myStats['notes_signed_this_week']" />
            <x-dashboard-stat icon="clock" color="amber" :label="__('My unsigned drafts')" :value="$this->myStats['unsigned_drafts']" />
        </div>
    </div>

    @if ($this->adminStats)
        <div>
            <flux:heading size="lg" class="mb-3">{{ __('Administration') }}</flux:heading>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <x-dashboard-stat icon="user-group" color="purple" :label="__('Staff users')" :value="$this->adminStats['users']" :href="route('admin.users')" />
                <x-dashboard-stat icon="identification" :label="__('Doctors')" :value="$this->adminStats['doctors']" :href="route('doctors.index')" />
                <x-dashboard-stat icon="building-office-2" :label="__('HHAs')" :value="$this->adminStats['agencies']" :href="route('home-health-agencies.index')" />
                <x-dashboard-stat icon="banknotes" :label="__('Insurance companies')" :value="$this->adminStats['insurers']" :href="route('insurance-companies.index')" />
                <x-dashboard-stat icon="shield-check" color="purple" :label="__('Activity today')" :value="$this->adminStats['activity_today']" :href="route('admin.audit-log')" />
            </div>
        </div>
    @endif
</div>
