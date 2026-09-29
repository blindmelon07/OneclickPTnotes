<?php

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

new #[Title('Manage Users')] class extends Component {
    use PasswordValidationRules, ProfileValidationRules, WithPagination;

    public string $search = '';

    public ?int $editingUserId = null;

    /** @var array<int, string> */
    public array $selectedRoles = [];

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    /** @var array<int, string> */
    public array $newUserRoles = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function createUser(): void
    {
        abort_unless(auth()->user()->can('users.manage'), 403);

        $this->reset(['name', 'email', 'password', 'password_confirmation', 'newUserRoles']);

        Flux::modal('user-form')->show();
    }

    public function saveUser(): void
    {
        abort_unless(auth()->user()->can('users.manage'), 403);

        $validated = $this->validate([
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        $user->syncRoles($this->newUserRoles);

        activity('user')
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties(['roles' => $this->newUserRoles])
            ->log('user created');

        Flux::modal('user-form')->close();

        $this->reset(['name', 'email', 'password', 'password_confirmation', 'newUserRoles']);

        Flux::toast(variant: 'success', text: __('User created.'));
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
     * Disabling blocks future logins (see the Login listener in
     * AppServiceProvider) and immediately kills any of their active sessions
     * by deleting the session-driver rows tied to their user_id.
     */
    public function toggleDisabled(int $userId): void
    {
        abort_unless(auth()->user()->can('users.manage'), 403);

        $user = User::findOrFail($userId);

        abort_if($user->is(auth()->user()), 403, "You can't disable your own account.");

        $user->forceFill([
            'disabled_at' => $user->isDisabled() ? null : now(),
        ])->save();

        if ($user->isDisabled()) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        activity('user')
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->log($user->isDisabled() ? 'user disabled' : 'user enabled');

        Flux::toast(variant: 'success', text: $user->isDisabled()
            ? __(':name has been disabled.', ['name' => $user->name])
            : __(':name has been re-enabled.', ['name' => $user->name]));
    }

    /**
     * Forces a still-enabled user to re-authenticate everywhere, without
     * disabling their account — for a suspected-compromised session.
     */
    public function signOutEverywhere(int $userId): void
    {
        abort_unless(auth()->user()->can('users.manage'), 403);

        $user = User::findOrFail($userId);

        DB::table('sessions')->where('user_id', $user->id)->delete();

        activity('user')
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->log('sessions revoked');

        Flux::toast(variant: 'success', text: __(':name has been signed out everywhere.', ['name' => $user->name]));
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
        @can('users.manage')
            <div class="mb-4 flex justify-end">
                <flux:button variant="primary" icon="plus" wire:click="createUser">
                    {{ __('New user') }}
                </flux:button>
            </div>
        @endcan

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
                        <flux:table.cell variant="strong">
                            {{ $user->name }}
                            @if ($user->isDisabled())
                                <flux:badge size="sm" color="red" class="ms-1">{{ __('Disabled') }}</flux:badge>
                            @endif
                        </flux:table.cell>
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
                                <div class="flex flex-wrap gap-1">
                                    <flux:button size="sm" variant="ghost" wire:click="editRoles({{ $user->id }})">
                                        {{ __('Edit roles') }}
                                    </flux:button>

                                    @unless ($user->is(auth()->user()))
                                        <flux:button
                                            size="sm"
                                            variant="ghost"
                                            wire:click="toggleDisabled({{ $user->id }})"
                                            wire:confirm="{{ $user->isDisabled() ? __('Re-enable this user?') : __('Disable this user? They will be signed out immediately and unable to log in.') }}"
                                        >
                                            {{ $user->isDisabled() ? __('Enable') : __('Disable') }}
                                        </flux:button>
                                    @endunless

                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        wire:click="signOutEverywhere({{ $user->id }})"
                                        wire:confirm="{{ __('Sign this user out of all their active sessions?') }}"
                                    >
                                        {{ __('Sign out everywhere') }}
                                    </flux:button>
                                </div>
                            @endcan
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </x-pages::admin.layout>

    <flux:modal name="user-form" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('New user') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Create an account and choose its roles.') }}</flux:text>
            </div>

            <flux:input wire:model="name" :label="__('Name')" required autofocus />

            <flux:input wire:model="email" :label="__('Email address')" type="email" required />

            <flux:input wire:model="password" :label="__('Password')" type="password" viewable required />

            <flux:input wire:model="password_confirmation" :label="__('Confirm password')" type="password" viewable required />

            <div class="space-y-2">
                <flux:label>{{ __('Roles') }}</flux:label>

                @foreach ($this->roles as $role)
                    <flux:field variant="inline">
                        <flux:checkbox wire:model="newUserRoles" value="{{ $role->name }}" />
                        <flux:label>{{ $role->name }}</flux:label>
                    </flux:field>
                @endforeach
            </div>

            <div class="flex">
                <flux:spacer />
                <flux:button type="button" variant="primary" wire:click="saveUser">
                    {{ __('Create user') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

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
