<?php

use App\Models\Patient;
use App\Models\PatientColor;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Calendar')] class extends Component {
    use WithPagination;

    /**
     * How many patients the "Patient colors" legend shows per page.
     */
    protected const PATIENTS_PER_PAGE = 5;

    /**
     * The palette a viewer can pick from, both for their default calendar accent
     * color and for an individual patient's color. Kept as literal Tailwind class
     * strings (not built from interpolation) so Tailwind's build-time scanner
     * picks every one of them up.
     *
     * @var array<int, string>
     */
    public const COLORS = [
        'red', 'orange', 'amber', 'yellow', 'lime', 'green',
        'teal', 'blue', 'indigo', 'purple', 'pink',
    ];

    public string $month = '';

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
    }

    public function previousMonth(): void
    {
        $this->month = $this->cursor->copy()->subMonthNoOverflow()->format('Y-m');
        $this->resetPage();
    }

    public function nextMonth(): void
    {
        $this->month = $this->cursor->copy()->addMonthNoOverflow()->format('Y-m');
        $this->resetPage();
    }

    public function goToToday(): void
    {
        $this->month = now()->format('Y-m');
        $this->resetPage();
    }

    /**
     * Set the viewer's default calendar accent color (the fallback used for any
     * patient without their own color assigned).
     */
    public function setColor(string $color): void
    {
        abort_unless(in_array($color, self::COLORS, true), 422);

        auth()->user()->update(['calendar_color' => $color]);
    }

    /**
     * Set the viewer's own color for one patient. Personal to this viewer —
     * doesn't affect how anyone else sees that patient.
     */
    public function setPatientColor(int $patientId, string $color): void
    {
        abort_unless(in_array($color, self::COLORS, true), 422);

        $patient = Patient::findOrFail($patientId);

        PatientColor::updateOrCreate(
            ['user_id' => auth()->id(), 'patient_id' => $patient->id],
            ['color' => $color],
        );

        unset($this->patientColors);
    }

    #[Computed]
    public function cursor(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', $this->month.'-01')->startOfDay();
    }

    /**
     * The logged-in therapist's visits across the displayed month's calendar grid
     * (including the leading/trailing days from adjacent months shown to fill it).
     *
     * @return Collection<int, Visit>
     */
    #[Computed]
    public function visits(): Collection
    {
        $monthStart = $this->cursor->copy()->startOfMonth();
        $monthEnd = $this->cursor->copy()->endOfMonth();
        $gridStart = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $monthEnd->copy()->endOfWeek(Carbon::SUNDAY);

        return Visit::query()
            ->where('therapist_id', auth()->id())
            ->whereBetween('scheduled_at', [$gridStart, $gridEnd])
            ->with('patient')
            ->orderBy('scheduled_at')
            ->get();
    }

    /**
     * A Monday-start grid of weeks covering the displayed month, each day carrying
     * that day's visits.
     *
     * @return array<int, array<int, array{date: Carbon, inMonth: bool, isToday: bool, visits: Collection<int, Visit>}>>
     */
    #[Computed]
    public function weeks(): array
    {
        $monthStart = $this->cursor->copy()->startOfMonth();
        $monthEnd = $this->cursor->copy()->endOfMonth();
        $gridStart = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $monthEnd->copy()->endOfWeek(Carbon::SUNDAY);

        $visitsByDay = $this->visits->groupBy(fn (Visit $visit) => $visit->scheduled_at->format('Y-m-d'));

        $weeks = [];
        $day = $gridStart->copy();

        while ($day <= $gridEnd) {
            $week = [];

            for ($i = 0; $i < 7; $i++) {
                $week[] = [
                    'date' => $day->copy(),
                    'inMonth' => $day->month === $monthStart->month,
                    'isToday' => $day->isToday(),
                    'visits' => $visitsByDay->get($day->format('Y-m-d'), collect()),
                ];

                $day->addDay();
            }

            $weeks[] = $week;
        }

        return $weeks;
    }

    /**
     * The distinct patients appearing on the displayed grid, for the color legend
     * (paginated — a busy month can easily list more patients than fit on screen).
     *
     * @return LengthAwarePaginator<int, Patient>
     */
    #[Computed]
    public function patients(): LengthAwarePaginator
    {
        $patientIds = $this->visits->pluck('patient_id')->unique();

        return Patient::query()
            ->whereIn('id', $patientIds)
            ->orderBy('name')
            ->paginate(self::PATIENTS_PER_PAGE);
    }

    /**
     * The viewer's own color overrides, keyed by patient id.
     *
     * @return Collection<int, string>
     */
    #[Computed]
    public function patientColors(): Collection
    {
        return PatientColor::where('user_id', auth()->id())->pluck('color', 'patient_id');
    }

    /**
     * The color to render a given patient's visits in: their own override if the
     * viewer has set one, otherwise the viewer's default calendar accent color.
     */
    public function colorForPatient(int $patientId): string
    {
        return $this->patientColors->get($patientId) ?? auth()->user()->calendar_color ?? 'red';
    }

    /**
     * The Tailwind classes for a "today" badge, keyed by palette color.
     */
    public function todayClasses(): string
    {
        return match (auth()->user()->calendar_color) {
            'orange' => 'bg-orange-600 text-white',
            'amber' => 'bg-amber-600 text-white',
            'yellow' => 'bg-yellow-500 text-white',
            'lime' => 'bg-lime-600 text-white',
            'green' => 'bg-green-600 text-white',
            'teal' => 'bg-teal-600 text-white',
            'blue' => 'bg-blue-600 text-white',
            'indigo' => 'bg-indigo-600 text-white',
            'purple' => 'bg-purple-600 text-white',
            'pink' => 'bg-pink-600 text-white',
            default => 'bg-red-600 text-white',
        };
    }

    /**
     * The Tailwind classes for a visit chip in the given palette color.
     */
    public function chipClasses(string $color): string
    {
        return match ($color) {
            'orange' => 'bg-orange-50 text-orange-800 hover:bg-orange-100 dark:bg-orange-950 dark:text-orange-200 dark:hover:bg-orange-900',
            'amber' => 'bg-amber-50 text-amber-800 hover:bg-amber-100 dark:bg-amber-950 dark:text-amber-200 dark:hover:bg-amber-900',
            'yellow' => 'bg-yellow-50 text-yellow-800 hover:bg-yellow-100 dark:bg-yellow-950 dark:text-yellow-200 dark:hover:bg-yellow-900',
            'lime' => 'bg-lime-50 text-lime-800 hover:bg-lime-100 dark:bg-lime-950 dark:text-lime-200 dark:hover:bg-lime-900',
            'green' => 'bg-green-50 text-green-800 hover:bg-green-100 dark:bg-green-950 dark:text-green-200 dark:hover:bg-green-900',
            'teal' => 'bg-teal-50 text-teal-800 hover:bg-teal-100 dark:bg-teal-950 dark:text-teal-200 dark:hover:bg-teal-900',
            'blue' => 'bg-blue-50 text-blue-800 hover:bg-blue-100 dark:bg-blue-950 dark:text-blue-200 dark:hover:bg-blue-900',
            'indigo' => 'bg-indigo-50 text-indigo-800 hover:bg-indigo-100 dark:bg-indigo-950 dark:text-indigo-200 dark:hover:bg-indigo-900',
            'purple' => 'bg-purple-50 text-purple-800 hover:bg-purple-100 dark:bg-purple-950 dark:text-purple-200 dark:hover:bg-purple-900',
            'pink' => 'bg-pink-50 text-pink-800 hover:bg-pink-100 dark:bg-pink-950 dark:text-pink-200 dark:hover:bg-pink-900',
            default => 'bg-red-50 text-red-800 hover:bg-red-100 dark:bg-red-950 dark:text-red-200 dark:hover:bg-red-900',
        };
    }

    /**
     * The Tailwind swatch class for a given palette color (used by the pickers).
     */
    public function swatchClasses(string $color): string
    {
        return match ($color) {
            'orange' => 'bg-orange-500',
            'amber' => 'bg-amber-500',
            'yellow' => 'bg-yellow-500',
            'lime' => 'bg-lime-500',
            'green' => 'bg-green-500',
            'teal' => 'bg-teal-500',
            'blue' => 'bg-blue-500',
            'indigo' => 'bg-indigo-500',
            'purple' => 'bg-purple-500',
            'pink' => 'bg-pink-500',
            default => 'bg-red-500',
        };
    }
}; ?>

<section class="w-full">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Calendar') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Your scheduled visits') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <flux:heading size="lg">{{ $this->cursor->format('F Y') }}</flux:heading>

        <flux:spacer />

        <flux:text class="text-sm">{{ __('Default color') }}</flux:text>
        <div class="flex items-center gap-1" role="group" aria-label="{{ __('Default calendar color') }}">
            @foreach (self::COLORS as $color)
                <flux:tooltip :content="ucfirst($color)">
                    <button
                        type="button"
                        wire:click="setColor('{{ $color }}')"
                        class="size-5 cursor-pointer rounded-full {{ $this->swatchClasses($color) }} {{ auth()->user()->calendar_color === $color ? 'ring-2 ring-offset-2 ring-zinc-800 dark:ring-white dark:ring-offset-zinc-800' : '' }}"
                        aria-label="{{ ucfirst($color) }}"
                    ></button>
                </flux:tooltip>
            @endforeach
        </div>

        <flux:separator vertical class="mx-1 h-6" />

        <flux:tooltip :content="__('Previous month')">
            <flux:button size="sm" icon="chevron-left" wire:click="previousMonth" />
        </flux:tooltip>
        <flux:button size="sm" wire:click="goToToday">{{ __('Today') }}</flux:button>
        <flux:tooltip :content="__('Next month')">
            <flux:button size="sm" icon="chevron-right" wire:click="nextMonth" />
        </flux:tooltip>
    </div>

    <div class="grid grid-cols-7 gap-px overflow-hidden rounded-lg border border-zinc-200 bg-zinc-200 dark:border-zinc-700 dark:bg-zinc-700">
        @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $label)
            <div class="bg-zinc-50 px-2 py-1.5 text-center text-xs font-medium text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">
                {{ __($label) }}
            </div>
        @endforeach

        @foreach ($this->weeks as $week)
            @foreach ($week as $day)
                <div class="min-h-28 bg-white p-1.5 dark:bg-zinc-800 {{ $day['inMonth'] ? '' : 'opacity-40' }}">
                    <div class="mb-1 flex justify-end">
                        <span class="flex size-6 items-center justify-center rounded-full text-xs {{ $day['isToday'] ? $this->todayClasses().' font-semibold' : 'text-zinc-500 dark:text-zinc-400' }}">
                            {{ $day['date']->format('j') }}
                        </span>
                    </div>

                    <div class="space-y-1">
                        @foreach ($day['visits'] as $visit)
                            <flux:link
                                :href="route('patients.show', $visit->patient)"
                                wire:navigate
                                class="block truncate rounded-sm px-1 py-0.5 text-xs no-underline {{ $this->chipClasses($this->colorForPatient($visit->patient_id)) }}"
                            >
                                {{ $visit->scheduled_at->format('g:i A') }} {{ $visit->patient->name }}
                                <span class="font-medium">({{ strtoupper($visit->visit_type) }})</span>
                            </flux:link>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endforeach
    </div>

    @if ($this->patients->total() > 0)
        <div class="mt-6">
            <flux:heading size="sm" class="mb-3">{{ __('Patient colors') }}</flux:heading>
            <flux:text class="mb-3 text-sm">
                {{ __('Give each patient their own color on your calendar. Patients without one use your default color above.') }}
            </flux:text>

            <div class="space-y-2">
                @foreach ($this->patients as $patient)
                    <div class="flex flex-wrap items-center gap-3 rounded-lg border border-zinc-200 p-2 dark:border-zinc-700">
                        <flux:link :href="route('patients.show', $patient)" wire:navigate class="w-40 shrink-0 truncate">
                            {{ $patient->name }}
                        </flux:link>

                        <div class="flex items-center gap-1" role="group" aria-label="{{ __(':name color', ['name' => $patient->name]) }}">
                            @foreach (self::COLORS as $color)
                                <flux:tooltip :content="ucfirst($color)">
                                    <button
                                        type="button"
                                        wire:click="setPatientColor({{ $patient->id }}, '{{ $color }}')"
                                        class="size-5 cursor-pointer rounded-full {{ $this->swatchClasses($color) }} {{ $this->colorForPatient($patient->id) === $color ? 'ring-2 ring-offset-2 ring-zinc-800 dark:ring-white dark:ring-offset-zinc-800' : '' }}"
                                        aria-label="{{ ucfirst($color) }}"
                                    ></button>
                                </flux:tooltip>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <flux:pagination :paginator="$this->patients" />
        </div>
    @endif
</section>
