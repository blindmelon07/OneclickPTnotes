<?php

use App\Models\HomeHealthAgency;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Home Health Agencies')] class extends Component {
    public ?int $editingId = null;

    public string $name = '';

    public string $phone = '';

    public string $fax = '';

    public string $address = '';

    public string $referred_by = '';

    public function create(): void
    {
        abort_unless(auth()->user()->can('patients.manage'), 403);

        $this->reset(['editingId', 'name', 'phone', 'fax', 'address', 'referred_by']);

        Flux::modal('hha-form')->show();
    }

    public function edit(int $id): void
    {
        abort_unless(auth()->user()->can('patients.manage'), 403);

        $hha = HomeHealthAgency::findOrFail($id);

        $this->editingId = $hha->id;
        $this->name = $hha->name;
        $this->phone = (string) $hha->phone;
        $this->fax = (string) $hha->fax;
        $this->address = (string) $hha->address;
        $this->referred_by = (string) $hha->referred_by;

        Flux::modal('hha-form')->show();
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('patients.manage'), 403);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'fax' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'referred_by' => ['nullable', 'string', 'max:255'],
        ]);

        HomeHealthAgency::updateOrCreate(['id' => $this->editingId], $validated);

        Flux::modal('hha-form')->close();

        Flux::toast(variant: 'success', text: __('Agency saved.'));
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()->can('patients.manage'), 403);

        HomeHealthAgency::findOrFail($id)->delete();

        Flux::toast(variant: 'success', text: __('Agency deleted.'));
    }

    /**
     * @return Collection<int, HomeHealthAgency>
     */
    #[Computed]
    public function agencies(): Collection
    {
        return HomeHealthAgency::withCount('patients')->orderBy('name')->get();
    }
}; ?>

<section class="w-full">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Home Health Agencies') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Referring agencies') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    @can('patients.manage')
        <div class="mb-4 flex justify-end">
            <flux:button variant="primary" icon="plus" wire:click="create">{{ __('New agency') }}</flux:button>
        </div>
    @endcan

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Phone') }}</flux:table.column>
            <flux:table.column>{{ __('Referred by') }}</flux:table.column>
            <flux:table.column>{{ __('Patients') }}</flux:table.column>
            <flux:table.column>{{ __('Actions') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->agencies as $hha)
                <flux:table.row :key="$hha->id">
                    <flux:table.cell variant="strong">{{ $hha->name }}</flux:table.cell>
                    <flux:table.cell>{{ $hha->phone }}</flux:table.cell>
                    <flux:table.cell>{{ $hha->referred_by }}</flux:table.cell>
                    <flux:table.cell>{{ $hha->patients_count }}</flux:table.cell>
                    <flux:table.cell>
                        @can('patients.manage')
                            <div class="flex gap-2">
                                <flux:button size="sm" variant="ghost" wire:click="edit({{ $hha->id }})">{{ __('Edit') }}</flux:button>
                                <flux:button size="sm" variant="ghost" wire:click="delete({{ $hha->id }})" wire:confirm="{{ __('Delete this agency?') }}">{{ __('Delete') }}</flux:button>
                            </div>
                        @endcan
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal name="hha-form" class="md:w-96">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? __('Edit agency') : __('New agency') }}</flux:heading>
            <flux:input wire:model="name" :label="__('Name')" required autofocus />
            <flux:input wire:model="phone" :label="__('Phone')" />
            <flux:input wire:model="fax" :label="__('Fax')" />
            <flux:input wire:model="address" :label="__('Address')" />
            <flux:input wire:model="referred_by" :label="__('Referred by')" />
            <div class="flex">
                <flux:spacer />
                <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
