<?php

namespace App\Models;

use Database\Factories\OutpatientFormFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * The client's outpatient PT paperwork for one case, ported from their
 * Initial Evaluation, Re-Evaluation and Discharge Summary forms. The VA fills
 * in the IE once; the RE's "(IE: ___)" column and the DC's "Initial / Baseline
 * Values" copy from it unless set separately, and so does the treatment plan.
 */
#[Fillable(['created_by', 'patient_name', 'diagnosis', 'data'])]
class OutpatientForm extends Model
{
    /** @use HasFactory<OutpatientFormFactory> */
    use HasFactory, LogsActivity;

    public const DOCUMENT_INITIAL_EVALUATION = 'initial-evaluation';

    public const DOCUMENT_RE_EVALUATION = 're-evaluation';

    public const DOCUMENT_DISCHARGE_SUMMARY = 'discharge-summary';

    public const DOCUMENT_ALL = 'all';

    public const RECOMMENDATION_CONTINUE = 'continue';

    public const RECOMMENDATION_DISCHARGE = 'discharge';

    public const RECOMMENDATION_CONTINUED_THERAPY = 'continued_therapy';

    public const RECOMMENDATION_AWAITING = 'awaiting';

    /**
     * Fixed — the D/C plan printed on the initial evaluation.
     */
    public const DISCHARGE_PLAN = 'SELF/HOME EXERCISE PROGRAM';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The generated forms, keyed by the slug used in the PDF route.
     *
     * @return array<string, string>
     */
    public static function documents(): array
    {
        return [
            self::DOCUMENT_INITIAL_EVALUATION => 'Initial Evaluation',
            self::DOCUMENT_RE_EVALUATION => 'Re-Evaluation',
            self::DOCUMENT_DISCHARGE_SUMMARY => 'Discharge Summary',
        ];
    }

    /**
     * An empty case: the shared patient header, then one block per form.
     *
     * @return array<string, mixed>
     */
    public static function blankData(): array
    {
        $pinch = ['ti' => '', 'tm' => '', 'tr' => '', 'tl' => ''];
        $grip = ['right' => '', 'left' => ''];
        $plan = array_fill_keys(array_keys(self::treatmentPlanItems()), '');
        $recommendation = [
            'recommendation' => '',
            'visits_left' => '',
            'discharge_reason' => '',
            'continued_frequency' => '',
            'continued_duration' => '',
        ];

        return [
            'patient_name' => '',
            'age' => '',
            'dob' => '',
            'hand' => '',
            'diagnosis' => '',
            'doi' => '',
            'dosx' => '',
            'ie' => [
                'date' => '',
                'history' => '',
                'chief_complaints' => '',
                'rom' => '',
                'mmt' => '',
                'grip' => $grip,
                'pinch' => ['right' => $pinch, 'left' => $pinch],
                'gait_wnl' => false,
                'assistive_device' => '',
                'walk_distance' => '',
                'walk_with_device' => '',
                'person_assist' => '',
                'stair_flights' => '',
                'sensory' => '',
                'numbness_on' => '',
                'xray' => '',
                'xray_findings' => '',
                'mri' => '',
                'mri_findings' => '',
                'special_test' => '',
                'palpation_sign' => '',
                'palpation_grade' => '',
                'tenderness_on' => '',
                'work_status' => [],
                'adl_wnl' => false,
                'adl_difficulties' => [],
                'adl_other' => '',
                'precautions' => [],
                'precaution_details' => array_fill_keys(self::detailedPrecautions(), ''),
                'precautions_other' => '',
                'goals' => [],
                'goal_details' => array_fill_keys(array_keys(self::goals()), ''),
                'plan' => $plan,
                'rehab_potential' => '',
                'rx_expiration_date' => '',
                'total_authorized_visits' => '',
                'frequency' => '',
                'duration' => '',
            ],
            're' => [
                'date' => '',
                'progress' => array_fill_keys(array_keys(self::progressRows()), ['value' => '', 'baseline' => '', 'status' => '']),
                'others_label' => '',
                'grip' => $grip,
                'pain_meds' => '',
                'objectives' => '',
                'working' => '',
                'comments' => '',
                'precautions' => '',
                'plan' => $plan,
                'still_complains' => '',
                'will_benefit' => '',
                ...$recommendation,
            ],
            'dc' => [
                'date' => '',
                'progress' => array_fill_keys(array_keys(self::progressRows()), ['baseline' => '', 'status' => '', 'value' => '']),
                'others_label' => '',
                'grip' => $grip,
                'pain_meds_initial' => '',
                'pain_meds_end' => '',
                'goals_status' => '',
                'goals_other' => '',
                'comments' => '',
                'working' => '',
                'work_duty' => '',
                'still_complains' => '',
                'benefit_decrease' => '',
                'benefit_increase' => '',
                'discharge_reasons' => [],
                'noncompliant_reason' => '',
                'discharge_reasons_other' => '',
                'surgery_date' => '',
                'surgery_on' => '',
                'plan' => $plan,
                'total_visits' => '',
                ...$recommendation,
            ],
        ];
    }

    /**
     * The stored data merged over the blank case, so every key is present.
     *
     * @return array<string, mixed>
     */
    public function formData(): array
    {
        return array_replace_recursive(self::blankData(), $this->data ?? []);
    }

    /**
     * @return array<int, string>
     */
    public static function hands(): array
    {
        return ['R-hand', 'L-hand'];
    }

    /**
     * @return array<int, string>
     */
    public static function workStatuses(): array
    {
        return ['Not Working', 'Student', 'Modified Duty', 'Part Time', 'Full Duty', 'Full Time', 'Disabled', 'Retired'];
    }

    /**
     * @return array<int, string>
     */
    public static function adlDifficulties(): array
    {
        return ['Squatting', 'Bending', 'Dressing UE garments', 'Dressing LE garments', 'Personal hygiene'];
    }

    /**
     * @return array<int, string>
     */
    public static function precautions(): array
    {
        return ['Cancer', 'Pacemaker', 'Seizures', 'DM1', 'DM2', 'Dialysis', 'Asthma', 'DVT', 'Blood clot'];
    }

    /**
     * The precautions the form prints a blank after, for the type / site.
     *
     * @return array<int, string>
     */
    public static function detailedPrecautions(): array
    {
        return ['Cancer', 'Dialysis'];
    }

    /**
     * @return array<string, string>
     */
    public static function goals(): array
    {
        return [
            'decrease_pain' => 'Decrease / eliminate pain',
            'decrease_tenderness' => 'Decrease / eliminate tenderness',
            'decrease_spasm' => 'Decrease / eliminate spasm',
            'increase_rom' => 'Increase ROM',
            'increase_mmt' => 'Increase MMT',
            'increase_adl' => 'Increase ADL FXN as to',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function treatmentPlanItems(): array
    {
        return [
            'modalities' => 'Modalities',
            'therapeutic_exercises' => 'Thera. exercises',
            'therapeutic_activities' => 'Thera. act',
            'gait_training' => 'Gait / transfer trng, stair trng',
            'neuromuscular_reeducation' => 'Neuromuscular reeducation',
            'manual_therapy' => 'Manual therapy',
            'adl_training' => 'ADL and self care training',
            'postural_training' => 'Postural training',
            'home_program' => 'Instructions on home exercise program and independent symptom mgt',
        ];
    }

    /**
     * Fixed — treatment plan lines printed the same on every form, never editable.
     *
     * @return array<string, string>
     */
    public static function fixedTreatmentPlan(): array
    {
        return [
            'adl_training' => 'Px education on self care and energy conservation technique',
            'postural_training' => 'Teach proper posture',
            'home_program' => 'Perform HEP as shown and instructed by PT.',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function rehabPotentials(): array
    {
        return ['Good', 'Fair', 'Poor'];
    }

    /**
     * The measures compared against the IE on the RE and the DC.
     *
     * @return array<string, string>
     */
    public static function progressRows(): array
    {
        return [
            'rom' => 'ROM',
            'mmt' => 'MMT',
            'pain' => 'Pain',
            'tenderness' => 'Tenderness',
            'spasm' => 'Spasm',
            'gait' => 'Gait',
            'posture' => 'Posture',
            'step_negotiation' => 'Step negotiation',
            'adl_function' => 'ADL function',
            'others' => 'Others',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function reEvaluationStatuses(): array
    {
        return ['Improving', 'Same', 'Worse'];
    }

    /**
     * @return array<int, string>
     */
    public static function dischargeStatuses(): array
    {
        return ['Improving', 'Same', 'Declined'];
    }

    /**
     * @return array<int, string>
     */
    public static function reEvaluationWorkingOptions(): array
    {
        return ['Yes', 'No', 'Modified Duty'];
    }

    /**
     * @return array<int, string>
     */
    public static function workDuties(): array
    {
        return ['Modified Duty', 'Full Work Load Duty'];
    }

    /**
     * @return array<string, string>
     */
    public static function recommendations(): array
    {
        return [
            self::RECOMMENDATION_CONTINUE => 'Continue with the remaining Physical Therapy visit',
            self::RECOMMENDATION_DISCHARGE => 'Recommend Discharge of Patient',
            self::RECOMMENDATION_CONTINUED_THERAPY => 'Recommend Continued Therapy (needs new doctor\'s prescription)',
            self::RECOMMENDATION_AWAITING => 'Awaiting your recommendation',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function goalStatuses(): array
    {
        return ['Met', 'Not Met', 'Partially Met', 'Others'];
    }

    /**
     * @return array<int, string>
     */
    public static function dischargeReasons(): array
    {
        return [
            'Maximum Rehab Potential had been met',
            'Patient will have surgery',
            'Patient is non-compliant',
            'Patient refused therapy',
            'Doctor / Adjuster Terminated Therapy',
            'Others',
        ];
    }

    /**
     * The IE value a progress row is measured against, as the IE recorded it.
     */
    public function initialValue(string $row): string
    {
        $ie = $this->formData()['ie'];

        return match ($row) {
            'rom' => $ie['rom'],
            'mmt' => $ie['mmt'],
            'tenderness' => $this->palpationSummary(),
            'gait' => $this->gaitSummary(),
            'step_negotiation' => filled($ie['stair_flights']) ? "Manages {$ie['stair_flights']} flight(s) of stairs/steps" : '',
            'adl_function' => $this->adlSummary(),
            default => '',
        };
    }

    /**
     * The baseline printed on the RE or DC: its own override, else the IE value.
     *
     * @param  're'|'dc'  $form
     */
    public function baselineFor(string $form, string $row): string
    {
        return ($this->formData()[$form]['progress'][$row]['baseline'] ?? '') ?: $this->initialValue($row);
    }

    /**
     * The treatment plan for a form. The RE copies the IE's and the DC copies
     * the RE's, line by line, unless the line is set separately. Fixed lines
     * always print their fixed text.
     *
     * @param  'ie'|'re'|'dc'  $form
     */
    public function planFor(string $form, string $item): string
    {
        if (isset(self::fixedTreatmentPlan()[$item])) {
            return self::fixedTreatmentPlan()[$item];
        }

        $data = $this->formData();
        $own = $data[$form]['plan'][$item] ?? '';

        return match ($form) {
            'ie' => $own,
            're' => $own ?: $this->planFor('ie', $item),
            'dc' => $own ?: $this->planFor('re', $item),
        };
    }

    /**
     * The RE's precautions copy the ones checked on the IE unless set separately.
     */
    public function reEvaluationPrecautions(): string
    {
        return $this->formData()['re']['precautions'] ?: $this->precautionSummary();
    }

    /**
     * The IE's precautions as one line, with each one's type / site.
     */
    public function precautionSummary(): string
    {
        $ie = $this->formData()['ie'];

        $precautions = array_map(
            fn (string $precaution): string => trim($precaution.' '.($ie['precaution_details'][$precaution] ?? '')),
            array_values(array_intersect(self::precautions(), $ie['precautions'])),
        );

        return implode(', ', array_filter([...$precautions, $ie['precautions_other']]));
    }

    public function gaitSummary(): string
    {
        $ie = $this->formData()['ie'];

        if ($ie['gait_wnl']) {
            return 'WNL';
        }

        return implode(', ', array_filter([
            filled($ie['walk_distance']) ? "Walks {$ie['walk_distance']} feet/mins" : '',
            match ($ie['walk_with_device']) {
                'with' => 'with assistive device'.(filled($ie['assistive_device']) ? " ({$ie['assistive_device']})" : ''),
                'without' => 'without assistive device',
                default => filled($ie['assistive_device']) ? "Device: {$ie['assistive_device']}" : '',
            },
            filled($ie['person_assist']) ? "{$ie['person_assist']} person assist" : '',
        ]));
    }

    public function palpationSummary(): string
    {
        $ie = $this->formData()['ie'];

        return implode(' ', array_filter([
            $ie['palpation_sign'],
            filled($ie['palpation_grade']) ? "Grade {$ie['palpation_grade']}" : '',
            filled($ie['tenderness_on']) ? "TTP on {$ie['tenderness_on']}" : '',
        ]));
    }

    public function adlSummary(): string
    {
        $ie = $this->formData()['ie'];

        if ($ie['adl_wnl']) {
            return 'WNL';
        }

        $difficulties = array_filter([...array_intersect(self::adlDifficulties(), $ie['adl_difficulties']), $ie['adl_other']]);

        return $difficulties ? 'Difficulty with '.implode(', ', $difficulties) : '';
    }

    /**
     * A progress row is ticked on the printed form once anything is entered for it.
     *
     * @param  're'|'dc'  $form
     */
    public function isProgressRowFilled(string $form, string $row): bool
    {
        $progress = $this->formData()[$form]['progress'][$row];

        return filled($progress['value']) || filled($progress['status']);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['created_by', 'patient_name', 'diagnosis', 'data'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('outpatient_form');
    }
}
