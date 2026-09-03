<div class="space-y-4">
    <flux:heading size="lg">{{ __('Missed Visit Report') }}</flux:heading>

    <div class="grid gap-4 sm:grid-cols-2">
        <flux:input wire:model="missedVisit.date" type="date" :label="__('Date')" required />
        <flux:input wire:model="missedVisit.agency_name" :label="__('Agency name')" />
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <flux:input wire:model="missedVisit.patient_name" :label="__('Patient name')" />
        <flux:input wire:model="missedVisit.mr_number" :label="__('MR #')" />
    </div>

    <div class="space-y-2">
        <flux:label>{{ __('Reason for cancellation / missed visit') }}</flux:label>
        <div class="grid gap-1 sm:grid-cols-2">
            @foreach (\App\Models\Note::missedVisitReasons() as $reason)
                <flux:field variant="inline">
                    <flux:checkbox wire:model="missedVisit.reasons" value="{{ $reason }}" />
                    <flux:label>{{ $reason }}</flux:label>
                </flux:field>
            @endforeach
        </div>
        <flux:input wire:model="missedVisit.reason_other" :label="__('Others')" />
        <flux:error name="missedVisit.reasons" />
    </div>

    <div class="space-y-2">
        <flux:label>{{ __('Notification thru') }}</flux:label>
        <flux:field variant="inline">
            <flux:checkbox wire:model="missedVisit.notified_by_fax" />
            <flux:label>{{ __('Fax') }}</flux:label>
        </flux:field>
        <flux:field variant="inline">
            <flux:checkbox wire:model="missedVisit.notified_by_phone" />
            <flux:label>{{ __('Phone') }}</flux:label>
        </flux:field>
        <flux:input wire:model="missedVisit.spoke_with" :label="__('Spoke with')" />
    </div>

    <div class="space-y-2">
        <flux:label>{{ __('Was the patient rescheduled?') }}</flux:label>
        <flux:radio.group wire:model.live="missedVisit.rescheduled">
            <flux:radio value="yes" :label="__('Yes')" />
            <flux:radio value="no" :label="__('No')" />
        </flux:radio.group>
        <flux:error name="missedVisit.rescheduled" />

        @if ($missedVisit['rescheduled'] === 'yes')
            <flux:input wire:model="missedVisit.next_visit_date" type="date" :label="__('Approximate next visit date')" />
        @elseif ($missedVisit['rescheduled'] === 'no')
            <flux:input wire:model="missedVisit.not_rescheduled_reason" :label="__('Reason')" />
        @endif
    </div>

    <flux:textarea wire:model="missedVisit.comments" rows="3" :label="__('Comments')" />

    @include('partials.visit-forms.letterhead', [
        'signatureModel' => 'missedVisitStaffSignature',
        'signature' => $missedVisitStaffSignature,
        'signedDate' => $missedVisit['date'] ?? null,
        'showSupervisingTherapist' => false,
    ])
</div>
