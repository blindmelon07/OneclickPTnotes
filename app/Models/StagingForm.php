<?php

namespace App\Models;

use Database\Factories\StagingFormFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * The client's "one click" PT paperwork, ported from their Home Health IE
 * spreadsheet. The VA fills in the evaluation (the spreadsheet's yellow cells)
 * once, and the four companion forms copy from it. The spreadsheet's green
 * cells are fixed content — see the `fixed*()` lists — and are never editable.
 */
#[Fillable(['created_by', 'patient_name', 'agency_name', 'data'])]
class StagingForm extends Model
{
    /** @use HasFactory<StagingFormFactory> */
    use HasFactory, LogsActivity;

    public const DOCUMENT_EVALUATION = 'evaluation';

    public const DOCUMENT_VISIT_NOTE = 'visit-note';

    public const DOCUMENT_TREATMENT_ORDER = 'treatment-order';

    public const DOCUMENT_PTA_COMMUNICATION = 'pta-communication';

    public const DOCUMENT_TEAM_CONFERENCE = 'team-conference';

    public const DOCUMENT_ALL = 'all';

    public const PTA_SCHEDULE_ROWS = 5;

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
            self::DOCUMENT_EVALUATION => 'P.T. / O.T. Evaluation',
            self::DOCUMENT_VISIT_NOTE => 'P.T. / O.T. Visit Note',
            self::DOCUMENT_TREATMENT_ORDER => 'P.T. / O.T. Treatment Order',
            self::DOCUMENT_PTA_COMMUNICATION => 'Communication Note — PTA Instructions',
            self::DOCUMENT_TEAM_CONFERENCE => 'Team Conference',
        ];
    }

    /**
     * An empty evaluation, with the spreadsheet's pre-filled defaults.
     *
     * @return array<string, mixed>
     */
    public static function blankData(): array
    {
        return [
            'agency_name' => '',
            'patient_name' => '',
            'mr_number' => '',
            'blood_pressure' => '',
            'heart_rate' => '',
            'diagnosis' => '',
            'evaluation_date' => '',
            'time_in' => '',
            'time_out' => '',
            'problems' => '',
            'short_term_goals' => '',
            'functional_level' => '',
            'functional_overrides' => array_fill_keys(array_keys(self::functionalActivities()), ''),
            'gait' => array_fill_keys(array_keys(self::gaitFields()), ''),
            'functional_limitations' => [],
            'functional_limitations_other' => '',
            'homebound_reasons' => [],
            'cannot_ambulate_distance' => '',
            'cognition' => [],
            'cognition_other' => '',
            'balance' => [
                'sitting_static' => '',
                'sitting_dynamic' => '',
                'standing_static' => '',
                'standing_dynamic' => '',
            ],
            'living_situation' => '',
            'precautions' => '',
            'rehab_potential' => '',
            'rom_exceptions' => '',
            'mmt_exceptions' => '',
            'activities_permitted' => [],
            'wheelchair_skills' => '',
            'prior_level_of_function' => 'PATIENT IS INDEPENDENT IN ALL ASPECTS OF ADL AND SELF-CARE. PATIENT FUNCTIONS WITH MINIMUM TO NO DIFFICULTY.',
            'palpation' => '',
            'pain' => '',
            'dme_needed' => '',
            'comments' => '',
            'pt_frequency' => '',
            'discharge_plans' => '',
            'plan_of_care' => '',
            'pta_name' => '',
            'patient_address' => '',
            'patient_phone' => '',
            'pta_start_week' => '',
            'pta_frequency' => '',
            'pta_schedule' => array_fill(0, self::PTA_SCHEDULE_ROWS, ['week_of' => '', 'visits' => '']),
        ];
    }

    /**
     * The stored data merged over the blank form, so every key is present.
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
    public static function assistLevels(): array
    {
        return [
            'Indep',
            'Partially Dep',
            'Supervised',
            'CareGiver Assist',
            'MIN assist',
            'MOD assist',
            'MAX assist',
            'UnAble',
            'Not Tested',
        ];
    }

    /**
     * Functional activities, grouped as on the form. Every activity inherits
     * the overall functional level unless the VA overrides it.
     *
     * @return array<string, array{group: string, label: string}>
     */
    public static function functionalActivities(): array
    {
        return [
            'roll_scoot' => ['group' => 'Bed mobility', 'label' => 'Roll / scoot'],
            'supine_to_sit' => ['group' => 'Bed mobility', 'label' => 'Supine to sit'],
            'sit_to_supine' => ['group' => 'Bed mobility', 'label' => 'Sit to supine'],
            'dressing' => ['group' => 'ADL function', 'label' => 'Dressing'],
            'personal_hygiene' => ['group' => 'ADL function', 'label' => 'Personal hygiene'],
            'bathing' => ['group' => 'ADL function', 'label' => 'Bathing'],
            'feeding' => ['group' => 'ADL function', 'label' => 'Feeding'],
            'meal_prep' => ['group' => 'ADL function', 'label' => 'Meal prep'],
            'home_making' => ['group' => 'ADL function', 'label' => 'Home making'],
            'transfer_bed' => ['group' => 'Transfers', 'label' => 'Bed'],
            'transfer_wheelchair' => ['group' => 'Transfers', 'label' => 'Wheelchair'],
            'transfer_toilet' => ['group' => 'Transfers', 'label' => 'Toilet / commode'],
            'transfer_shower' => ['group' => 'Transfers', 'label' => 'Shower'],
            'transfer_car' => ['group' => 'Transfers', 'label' => 'Car'],
            'transfer_floor' => ['group' => 'Transfers', 'label' => 'Floor'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function gaitFields(): array
    {
        return [
            'analysis_deviation' => 'Analysis / deviation',
            'assistive_device' => 'Assistive device',
            'level_surfaces' => 'Level surfaces',
            'uneven_surfaces' => 'Uneven surfaces',
            'stairs' => 'Stairs / step / curb',
            'minutes_distance' => 'Minutes / distance',
            'weight_bearing' => 'Weight bearing',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function functionalLimitations(): array
    {
        return [
            'Amputation',
            'DOE / SOB / wheezing',
            'Contractures',
            'Paralysis',
            'Weakness',
            'Endurance',
            'Fracture / surgery',
            'Bowel / bladder',
            'Speech',
            'Hearing',
            'Vision',
            'Ambulation',
        ];
    }

    /**
     * The "cannot ambulate" reason carries a distance, printed after it.
     *
     * @return array<int, string>
     */
    public static function homeboundReasons(): array
    {
        return [
            'Bed ridden / bed bound',
            'Cannot ambulate >',
            'Confusion / unable to go out alone',
            'Dependent upon assistive device',
            'Pain limits mobility',
            'Needs assistance on all activities',
            'Paralysis / weakness / hemiparesis',
            'Poor & limited endurance',
            '(+) Assist to ambulate / holds onto walls / furniture',
            'Severe SOB / SOB on exertion / wheezing',
            'Unable to safely leave home unassisted',
            'Unsteady gait / high risk for falls',
            'W/C bound w/ no access to stairs, cannot propel',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function cognitionOptions(): array
    {
        return ['Alert', 'Oriented', 'Lethargic', 'ST memory', 'Confused', 'Comatose', 'Agitated', 'LT memory'];
    }

    /**
     * @return array<int, string>
     */
    public static function balanceGrades(): array
    {
        return ['Poor-', 'Poor', 'Poor+', 'Fair-', 'Fair', 'Fair+', 'Good-', 'Good', 'Good+', 'Normal'];
    }

    /**
     * @return array<int, string>
     */
    public static function rehabPotentials(): array
    {
        return ['Poor', 'Fair', 'Good', 'Excellent'];
    }

    /**
     * @return array<int, string>
     */
    public static function activitiesPermitted(): array
    {
        return [
            'Complete bedrest',
            'Wheelchair',
            'Bedrest w/ BRP',
            'PWB',
            'Transfer bed / chair',
            'Ambulation w/ O2',
            'Exercise prescribed',
            'Cane / FWW / quad cane / crutches / rollator',
            'Ambulation w/ assist',
        ];
    }

    /**
     * Services on the visit note, left column then right column.
     *
     * @return array<int, string>
     */
    public static function visitServices(): array
    {
        return [
            'Initial evaluation',
            'Discharge evaluation',
            'Therapeutic exercises (stretch / strength)',
            'Therapeutic activities (functional)',
            'Gait training (w/ & w/o assistive device)',
            'Manual therapy (STM / MFR / joint mob / PNF)',
            'Neuromuscular reeducation',
            'Balance training (sitting / standing)',
            'Prosthetic training',
            'ADL and self care training',
            'Ultrasound',
            'Iontophoresis',
            'UVR / IRR',
            'Electrotherapy',
            'TENS',
            'CPM',
            'Teach bed mobility skills',
            'Teach hip safety precautions',
            'Teach use of assistive device',
            'Teach home exercise program (HEP)',
            'Teach patient fall precaution',
            'Teach wheelchair skills',
        ];
    }

    /**
     * Fixed (green) — always checked on the visit note.
     *
     * @return array<int, string>
     */
    public static function fixedVisitServices(): array
    {
        return [
            'Initial evaluation',
            'ADL and self care training',
            'Teach bed mobility skills',
            'Teach hip safety precautions',
            'Teach use of assistive device',
            'Teach home exercise program (HEP)',
            'Teach patient fall precaution',
        ];
    }

    /**
     * Fixed — the plan of care printed on the PTA instructions.
     *
     * @return array<int, string>
     */
    public static function ptaPlanOfCare(): array
    {
        return [
            'Energy conservation technique',
            'Stationary bike as tolerated only',
            'Home exercise program, sitting tolerance exercises, bed exercises, standing tolerance',
            'Conditioning exercise using both upper extremities and lower extremities',
            'Standing balance exercises of both lower extremities AP',
            'Standing tolerance training',
            'Gait training and step training',
            'Core strengthening program',
            'Transfer training, bed mobility exercises, Kegel\'s exercises',
            'Step and stair training',
        ];
    }

    /**
     * Fixed — the goals printed on the PTA instructions.
     *
     * @return array<int, string>
     */
    public static function ptaGoals(): array
    {
        return [
            'Increase transfer capabilities as to sit to stand / chair',
            'Increase MMT both upper extremities / lower extremities',
            'Independent in home exercises',
            'Independent on gait without difficulty',
            'Increase balance to good',
            'Return to prior level of function without difficulty and pain and to become independent on gait and transfers, ADL and self-care management',
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function teamConferenceProblems(): array
    {
        return [
            'Skilled nursing' => [
                'Significant changes in general condition',
                'Questionable medication effectiveness',
                'Impaired wound healing process',
                'Symptoms of infection',
                'Nutrition / PO intake',
                'Unreliable with meds / needs instruction',
                'Slow to comprehend medical tx instructions',
                'Poor bowel / bladder management',
                'Complicated medical treatment',
                'No complications identified',
            ],
            'Physical therapy / respiratory therapy' => [
                'Difficulty in transfer',
                'Decreased upright tolerance / endurance',
                'Change in mental status',
                'Poor safety / balance',
                'Decreased ROM / strength',
                'Pain',
                'Equipment / prosthesis',
                'Modalities required',
                'Difficulty w/ patient / family / follow through',
            ],
            'Occupational / respiratory therapy' => [
                'Difficulty with ADLs',
                'Decreased ROM / strength',
                'Decreased upright tolerance / endurance',
                'Decreased fine / gross motor coordination',
                'Problems w/ energy conservation / self pacing',
                'Pain',
                'Difficulty w/ cognitive perception',
                'Difficulty with patient family follow through',
            ],
            'Social services' => [
                'Living arrangements unsafe or inadequate for level of care',
                'Family unable to cope with situation',
                'Suspect physical / emotional abuse or neglect',
                'Patient having difficulty with med tx',
                'Patient is socially isolated',
                'Patient needs financial assistance / placement',
                'Patient needs community resource help',
            ],
        ];
    }

    /**
     * Fixed (green) — always checked under physical therapy on the team conference.
     *
     * @return array<int, string>
     */
    public static function fixedTeamConferenceProblems(): array
    {
        return [
            'Difficulty in transfer',
            'Decreased upright tolerance / endurance',
            'Poor safety / balance',
            'Decreased ROM / strength',
            'Pain',
            'Modalities required',
        ];
    }

    /**
     * The level printed for one activity: its override, else the overall level.
     */
    public function functionalLevelFor(string $activity): string
    {
        $data = $this->formData();

        return ($data['functional_overrides'][$activity] ?? '') ?: $data['functional_level'];
    }

    /**
     * A dynamic balance grade copies the static one unless set separately.
     */
    public function balanceGrade(string $position, string $kind): string
    {
        $balance = $this->formData()['balance'];

        if ($kind === 'dynamic') {
            return $balance["{$position}_dynamic"] ?: $balance["{$position}_static"];
        }

        return $balance["{$position}_static"];
    }

    /**
     * Minutes / distance copies the level-surfaces distance unless set separately.
     */
    public function gaitValue(string $field): string
    {
        $gait = $this->formData()['gait'];

        if ($field === 'minutes_distance') {
            return $gait['minutes_distance'] ?: $gait['level_surfaces'];
        }

        return $gait[$field] ?? '';
    }

    /**
     * The "cannot ambulate > __" distance, defaulting to the level-surfaces distance.
     */
    public function cannotAmbulateDistance(): string
    {
        return $this->formData()['cannot_ambulate_distance'] ?: $this->formData()['gait']['level_surfaces'];
    }

    /**
     * The scheduled PTA weeks that have been filled in.
     *
     * @return array<int, array{week_of: string, visits: string}>
     */
    public function ptaScheduleRows(): array
    {
        return array_values(array_filter(
            $this->formData()['pta_schedule'],
            fn (array $row): bool => filled($row['week_of']) || filled($row['visits']),
        ));
    }

    /**
     * Fixed (green) — the PTA's total is always the sum of the weekly visits.
     */
    public function totalPtaVisits(): int
    {
        return (int) array_sum(array_map(
            fn (array $row): int => (int) $row['visits'],
            $this->formData()['pta_schedule'],
        ));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['created_by', 'patient_name', 'agency_name', 'data'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('staging_form');
    }
}
