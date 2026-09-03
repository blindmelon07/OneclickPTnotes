<?php

use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

new #[Title('Manage Roles')] class extends Component {
    public ?int $editingRoleId = null;

    public string $name = '';

    /** @var array<int, string> */
    public array $selectedPermissions = [];

    public function createRole(): void
    {
        abort_unless(auth()->user()->can('roles.manage'), 403);

        $this->reset(['editingRoleId', 'name', 'selectedPermissions']);

        Flux::modal('role-form')->show();
    }

    public function editRole(int $roleId): void
    {
        abort_unless(auth()->user()->can('roles.manage'), 403);

        $role = Role::findOrFail($roleId);

        $this->editingRoleId = $role->id;
        $this->name = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('name')->all();

        Flux::modal('role-form')->show();
    }

    public function saveRole(): void
    {
        abort_unless(auth()->user()->can('roles.manage'), 403);

        $validated = $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Role::class, 'name')->ignore($this->editingRoleId),
            ],
        ]);

        $role = $this->editingRoleId
            ? Role::findOrFail($this->editingRoleId)
            : new Role(['guard_name' => 'web']);

        $isNew = ! $this->editingRoleId;

        $role->name = $validated['name'];
        $role->save();
        $role->syncPermissions($this->selectedPermissions);

        activity('role')
            ->causedBy(auth()->user())
            ->performedOn($role)
            ->withProperties(['name' => $role->name, 'permissions' => $this->selectedPermissions])
            ->log($isNew ? 'role created' : 'role updated');

        Flux::modal('role-form')->close();

        Flux::toast(variant: 'success', text: __('Role saved.'));
    }

    public function deleteRole(int $roleId): void
    {
        abort_unless(auth()->user()->can('roles.manage'), 403);

        $role = Role::findOrFail($roleId);

        if (in_array($role->name, ['admin', 'user'], true)) {
            Flux::toast(variant: 'danger', text: __('This role cannot be deleted.'));

            return;
        }

        activity('role')
            ->causedBy(auth()->user())
            ->performedOn($role)
            ->withProperties(['name' => $role->name])
            ->log('role deleted');

        $role->delete();

        Flux::toast(variant: 'success', text: __('Role deleted.'));
    }

    /**
     * @return Collection<int, Permission>
     */
    #[Computed]
    public function permissions(): Collection
    {
        return Permission::whereNotIn('name', User::RESTRICTIONS)->orderBy('name')->get();
    }

    /**
     * Restrictions are stored as permissions but take access away, so the role
     * editor lists them separately from the grants — see `User::RESTRICTIONS`.
     *
     * @return Collection<int, Permission>
     */
    #[Computed]
    public function restrictions(): Collection
    {
        return Permission::whereIn('name', User::RESTRICTIONS)
            ->get()
            ->sortBy(fn (Permission $permission) => array_search($permission->name, User::RESTRICTIONS, true))
            ->values();
    }

    /**
     * @return Collection<int, Role>
     */
    #[Computed]
    public function roles(): Collection
    {
        return Role::with('permissions')->withCount('users')->orderBy('name')->get();
    }
}; ?>

<section class="w-full">
    @include('partials.admin-heading')

    <x-pages::admin.layout :heading="__('Roles')" :subheading="__('Create roles and control which permissions they grant')">
        @can('roles.manage')
            <div class="mb-4 flex justify-end">
                <flux:button variant="primary" icon="plus" wire:click="createRole">
                    {{ __('New role') }}
                </flux:button>
            </div>
        @endcan

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Permissions') }}</flux:table.column>
                <flux:table.column>{{ __('Users') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->roles as $role)
                    <flux:table.row :key="$role->id">
                        <flux:table.cell variant="strong">{{ $role->name }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex flex-wrap gap-1">
                                @forelse ($role->permissions as $permission)
                                    <flux:badge size="sm">{{ $permission->name }}</flux:badge>
                                @empty
                                    <flux:text class="text-sm">{{ __('No permissions') }}</flux:text>
                                @endforelse
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>{{ $role->users_count }}</flux:table.cell>
                        <flux:table.cell>
                            @can('roles.manage')
                                <div class="flex gap-2">
                                    <flux:button size="sm" variant="ghost" wire:click="editRole({{ $role->id }})">
                                        {{ __('Edit') }}
                                    </flux:button>

                                    @unless (in_array($role->name, ['admin', 'user'], true))
                                        <flux:button
                                            size="sm"
                                            variant="ghost"
                                            wire:click="deleteRole({{ $role->id }})"
                                            wire:confirm="{{ __('Delete this role?') }}"
                                        >
                                            {{ __('Delete') }}
                                        </flux:button>
                                    @endunless
                                </div>
                            @endcan
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </x-pages::admin.layout>

    <flux:modal name="role-form" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingRoleId ? __('Edit role') : __('New role') }}</flux:heading>
            </div>

            <flux:input wire:model="name" :label="__('Name')" required autofocus />

            <div class="space-y-2">
                <flux:label>{{ __('Permissions') }}</flux:label>

                @foreach ($this->permissions as $permission)
                    <flux:field variant="inline">
                        <flux:checkbox wire:model="selectedPermissions" value="{{ $permission->name }}" />
                        <flux:label>{{ $permission->name }}</flux:label>
                    </flux:field>
                @endforeach
            </div>

            <div class="space-y-2">
                <flux:label>{{ __('Restrictions') }}</flux:label>
                <flux:text class="text-sm">{{ __('Take access away from this role. Leave unticked for full access.') }}</flux:text>

                @foreach ($this->restrictions as $restriction)
                    <flux:field variant="inline">
                        <flux:checkbox wire:model="selectedPermissions" value="{{ $restriction->name }}" />
                        <flux:label>{{ \App\Models\User::restrictionLabel($restriction->name) }}</flux:label>
                    </flux:field>
                @endforeach
            </div>

            <div class="flex">
                <flux:spacer />
                <flux:button type="button" variant="primary" wire:click="saveRole">
                    {{ __('Save') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>
