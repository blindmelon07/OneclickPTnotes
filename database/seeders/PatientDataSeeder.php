<?php

namespace Database\Seeders;

use App\Actions\Notes\GenerateNotePdf;
use App\Models\Doctor;
use App\Models\HomeHealthAgency;
use App\Models\InsuranceCompany;
use App\Models\Note;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class PatientDataSeeder extends Seeder
{
    /**
     * Seed sample doctors, insurance companies, agencies, patients, visits, and notes.
     */
    public function run(): void
    {
        $therapists = User::all();

        if ($therapists->isEmpty()) {
            $therapists = User::factory(3)->create();
        }

        $doctors = Doctor::factory(6)->create();
        $insuranceCompanies = InsuranceCompany::factory(4)->create();
        $agencies = HomeHealthAgency::factory(5)->create();

        Patient::factory(20)
            ->recycle($doctors)
            ->recycle($insuranceCompanies)
            ->recycle($agencies)
            ->state(fn () => [
                'status' => fake()->randomElement([
                    Patient::STATUS_ACTIVE,
                    Patient::STATUS_ACTIVE,
                    Patient::STATUS_ACTIVE,
                    Patient::STATUS_DISCHARGED,
                    Patient::STATUS_HOSPITALIZED,
                ]),
            ])
            ->create()
            ->each(fn (Patient $patient) => $this->seedNotesAndVisits($patient, $therapists));
    }

    /**
     * @param  Collection<int, User>  $therapists
     */
    protected function seedNotesAndVisits(Patient $patient, Collection $therapists): void
    {
        $therapist = $therapists->random();

        $this->createSignedNote($patient, Note::TYPE_IE, $therapist, now()->subDays(random_int(30, 120)));

        if (fake()->boolean(60)) {
            $this->createSignedNote($patient, Note::TYPE_RE, $therapist, now()->subDays(random_int(10, 29)));
        }

        foreach (range(1, random_int(0, 4)) as $ignored) {
            $date = now()->subDays(random_int(1, 9));

            if (fake()->boolean(70)) {
                $this->createSignedNote($patient, Note::TYPE_FU, $therapist, $date);
            } else {
                Note::factory()->for($patient)->create([
                    'type' => Note::TYPE_FU,
                    'author_id' => $therapist->id,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
            }
        }

        if ($patient->status === Patient::STATUS_DISCHARGED) {
            $this->createSignedNote($patient, Note::TYPE_DC, $therapist, now()->subDays(random_int(1, 5)));
        }

        if ($patient->status === Patient::STATUS_ACTIVE) {
            foreach (range(1, random_int(1, 3)) as $ignored) {
                Visit::factory()->for($patient)->create([
                    'therapist_id' => $therapist->id,
                    'visit_type' => Note::TYPE_FU,
                    'scheduled_at' => now()->addDays(random_int(1, 14))->setTime(random_int(8, 16), 0),
                ]);
            }
        }
    }

    /**
     * Create a note that's fully signed, PDF-rendered, and marked emailed to the office.
     */
    protected function createSignedNote(Patient $patient, string $type, User $therapist, CarbonInterface $date): Note
    {
        $note = Note::factory()->for($patient)->create([
            'type' => $type,
            'author_id' => $therapist->id,
            'created_at' => $date,
            'updated_at' => $date,
        ]);

        $signaturePath = "signatures/{$note->id}.png";
        Storage::disk('local')->put($signaturePath, $this->fakeSignature($therapist->name));

        $note->forceFill([
            'signature_path' => $signaturePath,
            'signed_at' => $date,
        ])->save();

        $note->forceFill([
            'pdf_path' => (new GenerateNotePdf)($note),
        ])->save();

        $note->forceFill([
            'emailed_to' => config('notes.office_email'),
            'emailed_at' => $date,
        ])->save();

        return $note;
    }

    /**
     * Render a hand-drawn-looking scribble as a PNG, standing in for a real
     * drawn signature so seeded notes look and print like a signed document.
     */
    protected function fakeSignature(string $name): string
    {
        $width = 400;
        $height = 140;

        $image = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);

        $ink = imagecolorallocate($image, 17, 24, 39);
        imagesetthickness($image, 2);

        $initials = collect(explode(' ', $name))
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');

        $x = 30;
        $baseline = 80;

        foreach (str_split($initials ?: 'X') as $letter) {
            $points = [];
            $strokes = random_int(5, 8);

            for ($i = 0; $i <= $strokes; $i++) {
                $points[] = $x + ($i * 6);
                $points[] = $baseline + (int) (sin($i * 1.3) * random_int(15, 30));
            }

            for ($i = 0; $i < count($points) - 2; $i += 2) {
                imageline($image, $points[$i], $points[$i + 1], $points[$i + 2], $points[$i + 3], $ink);
            }

            $x += $strokes * 6 + 12;
        }

        // A trailing flourish underline, like a signature's final stroke.
        imageline($image, 30, $baseline + 35, min($x, $width - 20), $baseline + 28, $ink);

        ob_start();
        imagepng($image);
        $contents = ob_get_clean();
        imagedestroy($image);

        return $contents;
    }
}
