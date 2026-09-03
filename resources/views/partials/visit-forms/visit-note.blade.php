<div class="space-y-4">
    <flux:heading size="lg">{{ __('PT / OT Visit Note') }}</flux:heading>

    <flux:input wire:model="visitNote.agency_name" :label="__('Agency name')" required />

    <div class="grid gap-4 sm:grid-cols-3">
        <flux:input wire:model="visitNote.patient_name" :label="__('Patient name')" />
        <flux:input wire:model="visitNote.mr_number" :label="__('MR #')" />
        <flux:input wire:model="visitNote.date" type="date" :label="__('Date')" required />
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <flux:input wire:model="visitNote.bp" :label="__('BP')" />
        <flux:input wire:model="visitNote.pr" :label="__('PR')" />
        <flux:input wire:model="visitNote.rr" :label="__('RR')" />
    </div>

    <flux:input wire:model="visitNote.pain" :label="__('Pain — location/s, type, intensity')" />

    <div class="space-y-2">
        <flux:label>{{ __('Homebound status') }}</flux:label>
        <div class="grid gap-1 sm:grid-cols-2">
            @foreach (\App\Models\Note::homeboundStatuses() as $status)
                <flux:field variant="inline">
                    <flux:checkbox wire:model="visitNote.homebound_statuses" value="{{ $status }}" />
                    <flux:label>{{ $status }}</flux:label>
                </flux:field>
            @endforeach
        </div>
        <flux:input wire:model="visitNote.homebound_other" :label="__('Others')" />
    </div>

    <div class="space-y-2">
        <flux:label>{{ __('Service/s provided') }}</flux:label>
        <div class="grid gap-1 sm:grid-cols-2">
            @foreach (\App\Models\Note::servicesProvided() as $service)
                <flux:field variant="inline">
                    <flux:checkbox wire:model="visitNote.services_provided" value="{{ $service }}" />
                    <flux:label>{{ $service }}</flux:label>
                </flux:field>
            @endforeach
        </div>
        <flux:input wire:model="visitNote.services_other" :label="__('Others')" />
    </div>

    <flux:textarea
        wire:model="visitNote.observations"
        rows="5"
        required
        :label="__('Observations & instructions during this visit')"
        :description="__('Specific treatments given, patient\'s reaction and comments, other pertinent findings and information.')"
    />

    <flux:textarea wire:model="visitNote.progress_towards_goal" rows="4" :label="__('Progress towards goal')" />

    @include('partials.visit-forms.letterhead', [
        'signatureModel' => 'visitNoteStaffSignature',
        'signature' => $visitNoteStaffSignature,
        'signedDate' => $visitNote['date'] ?? null,
    ])
</div>
