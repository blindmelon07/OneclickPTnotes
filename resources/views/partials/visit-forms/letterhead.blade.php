@php($letterhead = config('notes.letterhead'))
@php($signedOn = filled($signedDate ?? null) ? \Illuminate\Support\Carbon::parse($signedDate) : null)

<div class="space-y-4 rounded-xl border border-zinc-200 bg-zinc-50 p-4 text-sm dark:border-zinc-700 dark:bg-zinc-900">
    <flux:text class="text-xs uppercase tracking-wide">{{ __('Printed on this form') }}</flux:text>
    <flux:text class="text-sm">
        {{ ($showSupervisingTherapist ?? true)
            ? __('Sign below — the supervising therapist signs the printed copy.')
            : __('Sign below to report this.') }}
    </flux:text>

    <div class="grid gap-6 @if ($showSupervisingTherapist ?? true) sm:grid-cols-2 @endif">
        @if ($showSupervisingTherapist ?? true)
        <div>
            @if ($signature && \App\Models\Note::countersignaturePath())
                <img
                    src="{{ asset(config('notes.letterhead.supervising_therapist_signature')) }}"
                    alt="{{ $letterhead['supervising_therapist'] }}"
                    class="mb-2 h-16"
                >
            @elseif ($signature)
                <flux:text class="mb-2 text-xs">
                    {{ __('No signature on file — this line prints blank for wet signing.') }}
                </flux:text>
            @endif
            <div class="border-b border-zinc-400 pb-1 font-bold italic">{{ $letterhead['supervising_therapist'] }}</div>
            <div class="italic text-zinc-500">{{ $letterhead['supervising_therapist_title'] }}</div>
            <div class="mt-2 border-b border-zinc-400 pb-1 text-zinc-500">
                {{ __('Date:') }} {{ $signature ? $signedOn?->format('M j, Y') : '' }}
            </div>
        </div>
        @endif

        <div>
            @if ($signature)
                <div class="mb-2 rounded-md border border-zinc-300 bg-white p-2 dark:border-zinc-600">
                    <img src="{{ $signature }}" alt="{{ __('Signature') }}" class="h-24 w-full object-contain">
                </div>
                <div class="mb-2 flex justify-end">
                    <flux:button type="button" size="sm" variant="ghost" wire:click="clearSignature('{{ $signatureModel }}')">
                        {{ __('Sign again') }}
                    </flux:button>
                </div>
            @else
                <x-signature-pad :model="$signatureModel" class="mb-2" />
            @endif
            <flux:error :name="$signatureModel" />
            <div class="border-b border-zinc-400 pb-1 font-bold italic">{{ auth()->user()->name }}, PTA</div>
            <div class="italic text-zinc-500">{{ $letterhead['assistant_title'] }}</div>
            <div class="mt-2 border-b border-zinc-400 pb-1 text-zinc-500">
                {{ __('Date:') }} {{ $signedOn?->format('M j, Y') }}
            </div>
        </div>
    </div>

    <div class="border-t-2 border-zinc-400 pt-3 text-center font-bold">{{ __('THERAPY SERVICES PROVIDED BY:') }}</div>

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="font-bold">{{ $letterhead['practice'] }}</div>
            <div class="pl-4 font-bold">{{ $letterhead['tagline'] }}</div>
            <div class="text-xs text-zinc-500">{{ strtoupper($letterhead['address']) }}</div>
            <div class="text-xs text-zinc-500">
                {{ __('PHONE NO:') }} {{ $letterhead['phone'] }} &nbsp; {{ __('FAX NO.:') }} {{ $letterhead['fax'] }}
            </div>
        </div>
        <div class="text-xs text-zinc-500">
            <div>{{ __('Email Address:') }} <span class="text-blue-600 underline">{{ $letterhead['email'] }}</span></div>
            <div>{{ __('Website:') }} <span class="text-blue-600 underline">{{ $letterhead['website'] }}</span></div>
        </div>
    </div>
</div>
