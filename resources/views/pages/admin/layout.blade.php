<div class="flex items-start max-md:flex-col">
    <div class="me-10 w-full pb-4 md:w-[220px]">
        <flux:navlist aria-label="{{ __('Administration') }}">
            <flux:navlist.item :href="route('admin.users')" wire:navigate>{{ __('Users') }}</flux:navlist.item>
            <flux:navlist.item :href="route('admin.roles')" wire:navigate>{{ __('Roles') }}</flux:navlist.item>
            <flux:navlist.item :href="route('admin.permissions')" wire:navigate>{{ __('Permissions') }}</flux:navlist.item>
            <flux:navlist.item :href="route('admin.audit-log')" wire:navigate>{{ __('Audit Log') }}</flux:navlist.item>
        </flux:navlist>
    </div>

    <flux:separator class="md:hidden" />

    <div class="min-w-0 flex-1 self-stretch max-md:pt-6">
        <flux:heading>{{ $heading ?? '' }}</flux:heading>
        <flux:subheading>{{ $subheading ?? '' }}</flux:subheading>

        <div class="mt-5 w-full max-w-2xl">
            {{ $slot }}
        </div>
    </div>
</div>
