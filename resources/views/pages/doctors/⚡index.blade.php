<?php

use App\Models\Doctor;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Doctors')] class extends Component {
    public ?int $editingId = null;

    public string $name = '';

    public string $phone = '';

    public string $fax = '';

    public string $address = '';

    public string $email = '';

    public function create(): void
    {
        abort_unless(auth()->user()->can('patients.manage'), 403);

        $this->reset(['editingId', 'name', 'phone', 'fax', 'address', 'email']);

        Flux::modal('doctor-form')->show();
    }

    public function edit(int $id): void
    {
        abort_unless(auth()->user()->can('patients.manage'), 403);

        $doctor = Doctor::findOrFail($id);

        $this->editingId = $doctor->id;
        $this->name = $doctor->name;
        $this->phone = (string) $doctor->phone;
        $this->fax = (string) $doctor->fax;
        $this->address = (string) $doctor->address;
        $this->email = (string) $doctor->email;

        Flux::modal('doctor-form')->show();
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('patients.manage'), 403);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'fax' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        Doctor::updateOrCreate(['id' => $this->editingId], $validated);

        Flux::modal('doctor-form')->close();

        Flux::toast(variant: 'success', text: __('Doctor saved.'));
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()->can('patients.manage'), 403);

        Doctor::findOrFail($id)->delete();

        Flux::toast(variant: 'success', text: __('Doctor deleted.'));
    }

    /**
     * @return Collection<int, Doctor>
     */
    #[Computed]
    public function doctors(): Collection
    {
        return Doctor::withCount('patients')->orderBy('name')->get();
    }
}; ?>

<section class="w-full">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Doctors') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Referring physicians') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    @can('patients.manage')
        <div class="mb-4 flex justify-end">
            <flux:button variant="primary" icon="plus" wire:click="create">{{ __('New doctor') }}</flux:button>
        </div>
    @endcan

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Phone') }}</flux:table.column>
            <flux:table.column>{{ __('Email') }}</flux:table.column>
            <flux:table.column>{{ __('Patients') }}</flux:table.column>
            <flux:table.column>{{ __('Actions') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->doctors as $doctor)
                <flux:table.row :key="$doctor->id">
                    <flux:table.cell variant="strong">{{ $doctor->name }}</flux:table.cell>
                    <flux:table.cell>{{ $doctor->phone }}</flux:table.cell>
                    <flux:table.cell>{{ $doctor->email }}</flux:table.cell>
                    <flux:table.cell>{{ $doctor->patients_count }}</flux:table.cell>
                    <flux:table.cell>
                        @can('patients.manage')
                            <div class="flex gap-2">
                                <flux:button size="sm" variant="ghost" wire:click="edit({{ $doctor->id }})">{{ __('Edit') }}</flux:button>
                                <flux:button size="sm" variant="ghost" wire:click="delete({{ $doctor->id }})" wire:confirm="{{ __('Delete this doctor?') }}">{{ __('Delete') }}</flux:button>
                            </div>
                        @endcan
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal name="doctor-form" class="md:w-96">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? __('Edit doctor') : __('New doctor') }}</flux:heading>
            <flux:input wire:model="name" :label="__('Name')" required autofocus />
            <flux:input wire:model="phone" :label="__('Phone')" />
            <flux:input wire:model="fax" :label="__('Fax')" />
            <flux:input wire:model="address" :label="__('Address')" />
            <flux:input wire:model="email" type="email" :label="__('Email')" />
            <div class="flex">
                <flux:spacer />
                <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
