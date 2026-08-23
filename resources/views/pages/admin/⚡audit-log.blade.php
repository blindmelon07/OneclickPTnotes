<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

new #[Title('Audit Log')] class extends Component {
    use WithPagination;

    public string $search = '';

    public string $logName = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingLogName(): void
    {
        $this->resetPage();
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function logNames(): array
    {
        return Activity::query()->distinct()->orderBy('log_name')->pluck('log_name')->all();
    }

    #[Computed]
    public function activities()
    {
        return Activity::query()
            ->with(['causer', 'subject'])
            ->when($this->logName, fn ($query) => $query->where('log_name', $this->logName))
            ->when($this->search, fn ($query) => $query->where('description', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(15);
    }
}; ?>

<section class="w-full">
    @include('partials.admin-heading')

    <x-pages::admin.layout :heading="__('Audit Log')" :subheading="__('Who changed what, and when')">
        <div class="mb-4 flex flex-wrap items-end gap-3">
            <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Search description...')" icon="magnifying-glass" class="max-w-xs" />

            <flux:select wire:model.live="logName" :placeholder="__('All areas')" class="max-w-48">
                <flux:select.option value="">{{ __('All areas') }}</flux:select.option>
                @foreach ($this->logNames as $name)
                    <flux:select.option :value="$name">{{ ucwords(str_replace('_', ' ', $name)) }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <flux:table :paginate="$this->activities">
            <flux:table.columns>
                <flux:table.column>{{ __('When') }}</flux:table.column>
                <flux:table.column>{{ __('Who') }}</flux:table.column>
                <flux:table.column>{{ __('Area') }}</flux:table.column>
                <flux:table.column>{{ __('Event') }}</flux:table.column>
                <flux:table.column>{{ __('Subject') }}</flux:table.column>
                <flux:table.column>{{ __('Changes') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->activities as $activity)
                    <flux:table.row :key="$activity->id">
                        <flux:table.cell>{{ $activity->created_at->format('M j, Y g:i A') }}</flux:table.cell>
                        <flux:table.cell>{{ $activity->causer?->name ?? __('System') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm">{{ ucwords(str_replace('_', ' ', $activity->log_name)) }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $activity->description }}</flux:table.cell>
                        <flux:table.cell>
                            {{ $activity->subject_type ? class_basename($activity->subject_type) : '—' }}
                            @if ($activity->subject_id)
                                #{{ $activity->subject_id }}
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @php $changed = $activity->properties['attributes'] ?? $activity->properties; @endphp
                            @if (! empty($changed))
                                <flux:tooltip :content="$activity->properties->toJson(JSON_PRETTY_PRINT)">
                                    <flux:badge size="sm" color="zinc">{{ __(':count field(s)', ['count' => count($changed)]) }}</flux:badge>
                                </flux:tooltip>
                            @else
                                —
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </x-pages::admin.layout>
</section>
