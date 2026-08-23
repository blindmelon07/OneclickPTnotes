<?php

use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

new #[Title('Manage Users')] class extends Component {
    use WithPagination;

    public string $search = '';

    public ?int $editingUserId = null;

    /** @var array<int, string> */
    public array $selectedRoles = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function editRoles(int $userId): void
    {
        abort_unless(auth()->user()->can('users.manage'), 403);

        $user = User::findOrFail($userId);

        $this->editingUserId = $userId;
        $this->selectedRoles = $user->roles->pluck('name')->all();

        Flux::modal('edit-user-roles')->show();
    }

    public function saveRoles(): void
    {
        abort_unless(auth()->user()->can('users.manage'), 403);

        $user = User::findOrFail($this->editingUserId);
        $user->syncRoles($this->selectedRoles);

        activity('user')
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties(['roles' => $this->selectedRoles])
            ->log('roles updated');

        Flux::modal('edit-user-roles')->close();

        Flux::toast(variant: 'success', text: __('Roles updated for :name.', ['name' => $user->name]));
    }

    /**
     * @return Collection<int, Role>
     */
    #[Computed]
    public function roles(): Collection
    {
        return Role::orderBy('name')->get();
    }

    #[Computed]
    public function users()
    {
        return User::query()
            ->with('roles')
            ->when($this->search, fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
            ))
            ->orderBy('name')
            ->paginate(10);
    }
}; ?>

<section class="w-full">
    @include('partials.admin-heading')

    <x-pages::admin.layout :heading="__('Users')" :subheading="__('View users and manage their assigned roles')">
        <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Search users...')" icon="magnifying-glass" class="mb-4" />

        <flux:table :paginate="$this->users">
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Email') }}</flux:table.column>
                <flux:table.column>{{ __('Roles') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->users as $user)
                    <flux:table.row :key="$user->id">
                        <flux:table.cell variant="strong">{{ $user->name }}</flux:table.cell>
                        <flux:table.cell>{{ $user->email }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex flex-wrap gap-1">
                                @forelse ($user->roles as $role)
                                    <flux:badge size="sm">{{ $role->name }}</flux:badge>
                                @empty
                                    <flux:text class="text-sm">{{ __('No roles') }}</flux:text>
                                @endforelse
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            @can('users.manage')
                                <flux:button size="sm" variant="ghost" wire:click="editRoles({{ $user->id }})">
                                    {{ __('Edit roles') }}
                                </flux:button>
                            @endcan
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </x-pages::admin.layout>

    <flux:modal name="edit-user-roles" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Edit roles') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Choose which roles this user should have.') }}</flux:text>
            </div>

            <div class="space-y-2">
                @foreach ($this->roles as $role)
                    <flux:field variant="inline">
                        <flux:checkbox wire:model="selectedRoles" value="{{ $role->name }}" />
                        <flux:label>{{ $role->name }}</flux:label>
                    </flux:field>
                @endforeach
            </div>

            <div class="flex">
                <flux:spacer />
                <flux:button type="button" variant="primary" wire:click="saveRoles">
                    {{ __('Save') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>
