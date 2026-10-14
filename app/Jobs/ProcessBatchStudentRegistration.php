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
use Illuminate\Support\Str;

class ProcessBatchStudentRegistration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $programId,
        public array $studentsData
    ) {}

    public function handle(): void
    {
        /** @var Program|null $program */
        $program = Program::find($this->programId);
        if (!$program) {
            return;
        }

        // Helper to trim strings and convert whitespace-only or empty values to null
        $cleanNullable = function (?string $value): ?string {
            if ($value === null) {
                return null;
            }
            $trimmed = trim($value);
            return $trimmed === '' ? null : $trimmed;
        };

        // Helper to trim standard required strings
        $cleanString = function (?string $value, string $default = ''): string {
            if ($value === null) {
                return $default;
            }
            $trimmed = trim($value);
            return $trimmed === '' ? $default : $trimmed;
        };

        foreach ($this->studentsData as $data) {
            DB::transaction(function () use ($program, $data, $cleanNullable, $cleanString) {
                $registrationDate = now();

                $email = $cleanString($data['email'] ?? '');

                /** @var User|null $user */
                $user = User::query()->where('email', $email)->first();
                $plainPassword = null;

                if (!$user) {
                    $plainPassword = Str::random(10);
                    $user = User::query()->create([
                        'email' => $email,
                        'public_id' => (string) Str::uuid(),
                        'name' => trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')),
                        'password' => bcrypt($plainPassword),
                    ]);
                }

                $yearShort = date('y');

                // Generate application_number if missing
                $applicationNumber = $cleanNullable($data['application_number'] ?? null);
                if (empty($applicationNumber)) {
                    $prefix = 'APP' . $yearShort;
                    $sequence = Student::query()
                            ->where('application_number', 'LIKE', $prefix . '%')
                            ->count() + 1;
                    $applicationNumber = sprintf('%s%04d', $prefix, $sequence);
                }

                // Generate admission_number if missing
                $admissionNumber = $cleanNullable($data['admission_number'] ?? null);
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
                $studentNumber = $cleanNullable($data['student_number'] ?? null);
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
                    'first_name' => $cleanString($data['first_name'] ?? ''),
                    'middle_names' => $cleanNullable($data['middle_names'] ?? null),
                    'last_name' => $cleanString($data['last_name'] ?? ''),
                    'email' => $email,
                    'phone' => $cleanString($data['phone'] ?? ''),
                    'dob' => $data['dob'] ?? null,
                    'address' => $cleanNullable($data['address'] ?? null),
                    'emergency_contact' => $cleanNullable($data['emergency_contact'] ?? null),
                    'sex' => !empty($data['sex']) ? $data['sex'] : 'male',
                    'marital_status' => !empty($data['marital_status']) ? $data['marital_status'] : 'single',
                    'nationality' => !empty($data['nationality']) ? $data['nationality'] : 'Zambian',
                    'nrc_number' => $cleanNullable($data['nrc_number'] ?? null),
                    'passport_number' => $cleanNullable($data['passport_number'] ?? null),
                    'intake' => !empty($data['intake']) ? $data['intake'] : 'January',
                    'study_mode' => !empty($data['study_mode']) ? $data['study_mode'] : 'full_time',
                    'status' => 'registered',
                    'application_date' => $registrationDate,
                    'admission_date' => $registrationDate,
                ]);

                // Send Welcome Email if password was generated
                if ($plainPassword) {
                    Mail::to($student->email)->send(new WelcomeStudentMail($student, $plainPassword));
                }
            });
        }
    }
}
