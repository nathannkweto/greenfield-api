<?php declare(strict_types=1);

namespace App\Jobs;

use App\Mail\WelcomeStudentMail;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProcessCsvStudentImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $programId,
        public string $filePath
    ) {}

    public function handle(): void
    {
        /** @var Program|null $program */
        $program = Program::find($this->programId);
        $fullPath = Storage::path($this->filePath);

        if (!$program || !file_exists($fullPath)) {
            return;
        }

        if (($handle = fopen($fullPath, 'r')) !== false) {
            $header = fgetcsv($handle, 1000, ',');
            if (!$header) {
                fclose($handle);
                Storage::delete($this->filePath);
                return;
            }

            // Normalize CSV headers to snake_case
            $headers = array_map(fn($col) => Str::snake(trim($col)), $header);

            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                if (count($row) !== count($headers)) {
                    continue;
                }

                $data = array_combine($headers, $row);
                if (empty($data['email'])) {
                    continue;
                }

                DB::transaction(function () use ($program, $data) {
                    $registrationDate = now();

                    /** @var User|null $user */
                    $user = User::query()->where('email', $data['email'])->first();
                    $plainPassword = null;

                    if (!$user) {
                        $plainPassword = Str::random(10);
                        $user = User::query()->create([
                            'email' => $data['email'],
                            'public_id' => (string) Str::uuid(),
                            'name' => trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')),
                            'password' => bcrypt($plainPassword),
                        ]);
                    }

                    $yearShort = date('y');

                    // Generate application_number if missing
                    $applicationNumber = $data['application_number'] ?? null;
                    if (empty($applicationNumber)) {
                        $prefix = 'APP' . $yearShort;
                        $sequence = Student::query()
                                ->where('application_number', 'LIKE', $prefix . '%')
                                ->count() + 1;
                        $applicationNumber = sprintf('%s%04d', $prefix, $sequence);
                    }

                    // Generate admission_number if missing
                    $admissionNumber = $data['admission_number'] ?? null;
                    if (empty($admissionNumber)) {
                        $programCode = strtoupper($program->code ?? 'GEN');
                        $prefix = 'AD' . $yearShort . $programCode;
                        $sequence = Student::query()
                                ->where('program_id', $program->id)
                                ->whereNotNull('admission_number')
                                ->where('admission_number', 'LIKE', $prefix . '%')
                                ->count() + 1;
                        $admissionNumber = sprintf('%s%03d', $prefix, $sequence);
                    }

                    // Generate student_number if missing
                    $studentNumber = $data['student_number'] ?? null;
                    if (empty($studentNumber)) {
                        $schoolId = $program->school_id ?? 1;
                        $schoolPrefix = sprintf('%s%02d', $yearShort, $schoolId);
                        $sequence = Student::query()
                                ->whereHas('program', function ($query) use ($schoolId) {
                                    $query->where('school_id', $schoolId);
                                })
                                ->whereNotNull('student_number')
                                ->where('student_number', 'LIKE', $schoolPrefix . '%')
                                ->count() + 1;
                        $studentNumber = sprintf('%s%03d', $schoolPrefix, $sequence);
                    }

                    /** @var Student $student */
                    $student = Student::query()->create([
                        'public_id' => (string) Str::uuid(),
                        'user_id' => $user->id,
                        'program_id' => $program->id,
                        'application_number' => $applicationNumber,
                        'admission_number' => $admissionNumber,
                        'student_number' => $studentNumber,
                        'first_name' => $data['first_name'] ?? '',
                        'middle_names' => $data['middle_names'] ?? null,
                        'last_name' => $data['last_name'] ?? '',
                        'email' => $data['email'],
                        'phone' => $data['phone'] ?? '',
                        'dob' => $data['dob'] ?? null,
                        'address' => $data['address'] ?? null,
                        'emergency_contact' => $data['emergency_contact'] ?? null,
                        'sex' => $data['sex'] ?? 'male',
                        'marital_status' => $data['marital_status'] ?? 'single',
                        'nationality' => $data['nationality'] ?? 'Zambian',
                        'nrc_number' => $data['nrc_number'] ?? null,
                        'passport_number' => $data['passport_number'] ?? null,
                        'intake' => $data['intake'] ?? 'January',
                        'study_mode' => $data['study_mode'] ?? 'full_time',
                        'status' => 'registered',
                        'application_date' => $registrationDate,
                        'admission_date' => $registrationDate,
                    ]);

                    // Send Welcome Email
                    Mail::to($student->email)->send(new WelcomeStudentMail($student, $plainPassword));
                });
            }

            fclose($handle);
            Storage::delete($this->filePath);
        }
    }
}
