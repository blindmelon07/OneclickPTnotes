<?php

use App\Models\InsuranceCompany;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Insurance Companies')] class extends Component {
    public ?int $editingId = null;

    public string $name = '';

    public string $phone = '';

    public function create(): void
    {
        abort_unless(auth()->user()->can('patients.manage'), 403);

        $this->reset(['editingId', 'name', 'phone']);

        Flux::modal('insurance-form')->show();
    }

    public function edit(int $id): void
    {
        abort_unless(auth()->user()->can('patients.manage'), 403);

        $insurance = InsuranceCompany::findOrFail($id);

        $this->editingId = $insurance->id;
        $this->name = $insurance->name;
        $this->phone = (string) $insurance->phone;

        Flux::modal('insurance-form')->show();
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('patients.manage'), 403);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
        ]);

        InsuranceCompany::updateOrCreate(['id' => $this->editingId], $validated);

        Flux::modal('insurance-form')->close();

        Flux::toast(variant: 'success', text: __('Insurance company saved.'));
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()->can('patients.manage'), 403);

        InsuranceCompany::findOrFail($id)->delete();

        Flux::toast(variant: 'success', text: __('Insurance company deleted.'));
    }

    /**
     * @return Collection<int, InsuranceCompany>
     */
    #[Computed]
    public function insuranceCompanies(): Collection
    {
        return InsuranceCompany::withCount('patients')->orderBy('name')->get();
    }
}; ?>

<section class="w-full">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Insurance Companies') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Payers') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    @can('patients.manage')
        <div class="mb-4 flex justify-end">
            <flux:button variant="primary" icon="plus" wire:click="create">{{ __('New insurance company') }}</flux:button>
        </div>
    @endcan

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Phone') }}</flux:table.column>
            <flux:table.column>{{ __('Patients') }}</flux:table.column>
            <flux:table.column>{{ __('Actions') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->insuranceCompanies as $insurance)
                <flux:table.row :key="$insurance->id">
                    <flux:table.cell variant="strong">{{ $insurance->name }}</flux:table.cell>
                    <flux:table.cell>{{ $insurance->phone }}</flux:table.cell>
                    <flux:table.cell>{{ $insurance->patients_count }}</flux:table.cell>
                    <flux:table.cell>
                        @can('patients.manage')
                            <div class="flex gap-2">
                                <flux:button size="sm" variant="ghost" wire:click="edit({{ $insurance->id }})">{{ __('Edit') }}</flux:button>
                                <flux:button size="sm" variant="ghost" wire:click="delete({{ $insurance->id }})" wire:confirm="{{ __('Delete this insurance company?') }}">{{ __('Delete') }}</flux:button>
                            </div>
                        @endcan
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal name="insurance-form" class="md:w-96">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? __('Edit insurance company') : __('New insurance company') }}</flux:heading>
            <flux:input wire:model="name" :label="__('Name')" required autofocus />
            <flux:input wire:model="phone" :label="__('Phone')" />
            <div class="flex">
                <flux:spacer />
                <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
