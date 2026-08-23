<?php

use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Permission;

new #[Title('Manage Permissions')] class extends Component {
    public string $name = '';

    public function createPermission(): void
    {
        abort_unless(auth()->user()->can('permissions.manage'), 403);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique(Permission::class, 'name')],
        ]);

        $permission = Permission::create(['name' => $validated['name'], 'guard_name' => 'web']);

        activity('permission')
            ->causedBy(auth()->user())
            ->performedOn($permission)
            ->withProperties(['name' => $permission->name])
            ->log('permission created');

        $this->reset('name');

        Flux::toast(variant: 'success', text: __('Permission created.'));
    }

    public function deletePermission(int $permissionId): void
    {
        abort_unless(auth()->user()->can('permissions.manage'), 403);

        $permission = Permission::findOrFail($permissionId);

        activity('permission')
            ->causedBy(auth()->user())
            ->performedOn($permission)
            ->withProperties(['name' => $permission->name])
            ->log('permission deleted');

        $permission->delete();

        Flux::toast(variant: 'success', text: __('Permission deleted.'));
    }

    /**
     * @return Collection<int, Permission>
     */
    #[Computed]
    public function permissions(): Collection
    {
        return Permission::withCount('roles')->orderBy('name')->get();
    }
}; ?>

<section class="w-full">
    @include('partials.admin-heading')

    <x-pages::admin.layout :heading="__('Permissions')" :subheading="__('Define the permissions roles can be granted')">
        @can('permissions.manage')
            <form wire:submit="createPermission" class="mb-4 flex items-end gap-2">
                <flux:input wire:model="name" :label="__('New permission')" placeholder="e.g. reports.view" class="flex-1" />
                <flux:button type="submit" variant="primary">{{ __('Add') }}</flux:button>
            </form>
        @endcan

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Roles using it') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->permissions as $permission)
                    <flux:table.row :key="$permission->id">
                        <flux:table.cell variant="strong">{{ $permission->name }}</flux:table.cell>
                        <flux:table.cell>{{ $permission->roles_count }}</flux:table.cell>
                        <flux:table.cell>
                            @can('permissions.manage')
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    wire:click="deletePermission({{ $permission->id }})"
                                    wire:confirm="{{ __('Delete this permission?') }}"
                                >
                                    {{ __('Delete') }}
                                </flux:button>
                            @endcan
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </x-pages::admin.layout>
</section>
