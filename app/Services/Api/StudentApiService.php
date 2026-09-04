<?php declare(strict_types=1);

namespace App\Services\Api;

use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\File;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenAPI\Server\Api\StudentsApiInterface;
use OpenAPI\Server\Model\ApplicationRequest;
use OpenAPI\Server\Model\ApplicationResponse;
use OpenAPI\Server\Model\ApplicationResponseData;
use OpenAPI\Server\Model\DocumentUploadRequest;
use OpenAPI\Server\Model\ErrorResponse;
use OpenAPI\Server\Model\StandardResponse;
use OpenAPI\Server\Model\StudentEnrollmentRequest;
use OpenAPI\Server\Model\StudentsPublicIdRejectPostRequest;
use OpenAPI\Server\Model\ValidationErrorResponse;
use Throwable;

class StudentApiService implements StudentsApiInterface
{
    /**
     * Submit Admission Application
     *
     * @throws Throwable
     */
    public function applyPost(
        ApplicationRequest $ApplicationRequest
    ): ApplicationResponse|ErrorResponse|ValidationErrorResponse {
        /** @var Program|null $program */
        $program = Program::query()
            ->where('public_id', $ApplicationRequest->program_public_id)
            ->first();

        if (!$program) {
            return new ErrorResponse(
                message: 'Selected program not found.',
                error: 'NOT_FOUND'
            );
        }

        return DB::transaction(function () use ($ApplicationRequest, $program) {
            /** @var User $user */
            $user = User::query()->firstOrCreate(
                ['email' => $ApplicationRequest->email],
                [
                    'public_id' => (string) Str::uuid(),
                    'name' => trim($ApplicationRequest->first_name . ' ' . $ApplicationRequest->last_name),
                    'password' => bcrypt(Str::random(16)),
                ]
            );

            // Generate Application Number: APP + YY + SEQ
            $yearShort = date('y');
            $prefix = 'APP' . $yearShort;
            $sequence = Student::query()
                    ->where('application_number', 'LIKE', $prefix . '%')
                    ->count() + 1;
            $applicationNumber = sprintf('%s%04d', $prefix, $sequence);

            /** @var Student $student */
            $student = Student::query()->create([
                'public_id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'program_id' => $program->id,
                'application_number' => $applicationNumber,
                'first_name' => $ApplicationRequest->first_name,
                'middle_names' => $ApplicationRequest->middle_names,
                'last_name' => $ApplicationRequest->last_name,
                'email' => $ApplicationRequest->email,
                'phone' => $ApplicationRequest->phone,
                'dob' => $ApplicationRequest->dob,
                'address' => $ApplicationRequest->address,
                'emergency_contact' => $ApplicationRequest->emergency_contact,
                'sex' => $this->resolveValue($ApplicationRequest->sex),
                'marital_status' => $this->resolveValue($ApplicationRequest->marital_status),
                'nationality' => $ApplicationRequest->nationality,
                'nrc_number' => $ApplicationRequest->nrc_number,
                'passport_number' => $ApplicationRequest->passport_number,
                'intake' => $this->resolveValue($ApplicationRequest->intake),
                'study_mode' => $this->resolveValue($ApplicationRequest->study_mode),
                'status' => 'applied',
                'application_date' => now(),
            ]);

            // Link pre-uploaded files via HasFiles trait
            $fileCollections = array_filter([
                'nrc' => $ApplicationRequest->nrc_file_public_id,
                'passport' => $ApplicationRequest->passport_file_public_id,
                'certificate' => $ApplicationRequest->certificate_file_public_id,
            ]);

            foreach ($fileCollections as $collection => $fileId) {
                if ($fileId && File::query()->where('id', $fileId)->exists()) {
                    $student->attachFile($fileId, $collection);
                }
            }

            return new ApplicationResponse(
                status: 'success',
                message: 'Application submitted successfully.',
                data: new ApplicationResponseData(
                    user_public_id: (string) ($user->public_id ?? $user->id),
                    student_public_id: $student->public_id,
                    application_number: $student->application_number
                )
            );
        });
    }

    /**
     * Admit Applicant
     *
     * @throws Throwable
     */
    public function studentsPublicIdAdmitPost(
        string $public_id
    ): StandardResponse|ErrorResponse {
        /** @var Student|null $student */
        $student = Student::query()
            ->with('program')
            ->where('public_id', $public_id)
            ->first();

        if (!$student) {
            return new ErrorResponse(
                message: 'Student record not found.',
                error: 'NOT_FOUND'
            );
        }

        return DB::transaction(function () use ($student) {
            $yearShort = date('y');
            $programCode = strtoupper($student->program->code ?? 'GEN');
            $prefix = 'AD' . $yearShort . $programCode;

            $sequence = Student::query()
                    ->where('program_id', $student->program_id)
                    ->whereNotNull('admission_number')
                    ->where('admission_number', 'LIKE', $prefix . '%')
                    ->count() + 1;

            $admissionNumber = sprintf('%s%03d', $prefix, $sequence);

            $student->update([
                'status' => 'admitted',
                'admission_number' => $admissionNumber,
                'admission_date' => now(),
            ]);

            return new StandardResponse(
                status: 'success',
                message: 'Applicant admitted successfully.',
                data: new class(
                    public_id: $student->public_id,
                    admission_number: (string) $student->admission_number,
                    status: $student->status,
                    admission_date: $student->admission_date?->toIso8601String()
                ) {
                    public function __construct(
                        public string $public_id,
                        public string $admission_number,
                        public string $status,
                        public ?string $admission_date
                    ) {}
                }
            );
        });
    }

    /**
     * Attach Uploaded Document to Student
     */
    public function studentsPublicIdDocumentsPost(
        string $public_id,
        DocumentUploadRequest $DocumentUploadRequest
    ): StandardResponse|ErrorResponse {
        /** @var Student|null $student */
        $student = Student::query()->where('public_id', $public_id)->first();

        if (!$student) {
            return new ErrorResponse(
                message: 'Student record not found.',
                error: 'NOT_FOUND'
            );
        }

        /** @var File|null $file */
        $file = File::query()->find($DocumentUploadRequest->file_public_id);

        if (!$file) {
            return new ErrorResponse(
                message: 'Referenced file not found.',
                error: 'NOT_FOUND'
            );
        }

        $student->attachFile($file->id, $DocumentUploadRequest->collection);

        return new StandardResponse(
            status: 'success',
            message: 'Document attached successfully.',
            data: new class(
                file_public_id: (string) $file->id,
                collection: $DocumentUploadRequest->collection
            ) {
                public function __construct(
                    public string $file_public_id,
                    public string $collection
                ) {}
            }
        );
    }

    /**
     * Enroll Student in Curriculum Courses
     *
     * @throws Throwable
     */
    public function studentsPublicIdEnrollPost(
        string $public_id,
        StudentEnrollmentRequest $StudentEnrollmentRequest
    ): StandardResponse|ErrorResponse|ValidationErrorResponse {
        /** @var Student|null $student */
        $student = Student::query()->where('public_id', $public_id)->first();

        if (!$student) {
            return new ErrorResponse(
                message: 'Student record not found.',
                error: 'NOT_FOUND'
            );
        }

        return DB::transaction(function () use ($student, $StudentEnrollmentRequest) {
            $curricula = Curriculum::query()
                ->whereIn('public_id', $StudentEnrollmentRequest->course_offering_public_ids)
                ->get();

            $enrolledPublicIds = [];

            foreach ($curricula as $curriculum) {
                Enrollment::query()->firstOrCreate([
                    'student_id' => $student->id,
                    'curriculum_id' => $curriculum->id,
                ], [
                    'public_id' => (string) Str::uuid(),
                    'status' => 'enrolled',
                ]);

                $enrolledPublicIds[] = $curriculum->public_id;
            }

            return new StandardResponse(
                status: 'success',
                message: 'Student enrolled successfully.',
                data: new class(
                    student_public_id: $student->public_id,
                    enrolled_curricula: $enrolledPublicIds
                ) {
                    /** @param array<int, string> $enrolled_curricula */
                    public function __construct(
                        public string $student_public_id,
                        public array $enrolled_curricula
                    ) {}
                }
            );
        });
    }

    /**
     * Register Student Profile
     *
     * @throws Throwable
     */
    public function studentsPublicIdRegisterPost(
        string $public_id
    ): StandardResponse|ErrorResponse {
        /** @var Student|null $student */
        $student = Student::query()
            ->where('public_id', $public_id)
            ->with('program.school')
            ->first();

        if (!$student) {
            return new ErrorResponse(
                message: 'Student record not found.',
                error: 'NOT_FOUND'
            );
        }

        return DB::transaction(function () use ($student) {
            $yearShort = date('y');
            $schoolId = $student->program->school_id ?? 1;
            $schoolPrefix = sprintf('%s%02d', $yearShort, $schoolId);

            $sequence = Student::query()
                    ->whereHas('program', function (Builder $query) use ($schoolId) {
                        $query->where('school_id', $schoolId);
                    })
                    ->whereNotNull('student_number')
                    ->where('student_number', 'LIKE', $schoolPrefix . '%')
                    ->count() + 1;

            $studentNumber = sprintf('%s%03d', $schoolPrefix, $sequence);

            $student->update([
                'status' => 'registered',
                'student_number' => $studentNumber,
            ]);

            return new StandardResponse(
                status: 'success',
                message: 'Student registered successfully.',
                data: new class(
                    public_id: $student->public_id,
                    student_number: (string) $student->student_number,
                    status: $student->status
                ) {
                    public function __construct(
                        public string $public_id,
                        public string $student_number,
                        public string $status
                    ) {}
                }
            );
        });
    }

    /**
     * Reject Applicant
     */
    public function studentsPublicIdRejectPost(
        string $public_id,
        StudentsPublicIdRejectPostRequest $StudentsPublicIdRejectPostRequest
    ): StandardResponse|ErrorResponse {
        /** @var Student|null $student */
        $student = Student::query()
            ->where('public_id', $public_id)
            ->first();

        if (!$student) {
            return new ErrorResponse(
                message: 'Student record not found.',
                error: 'NOT_FOUND'
            );
        }

        $student->update([
            'status' => 'rejected',
            'rejection_date' => now(),
        ]);

        return new StandardResponse(
            status: 'success',
            message: 'Applicant application rejected.',
            data: new class(
                public_id: $student->public_id,
                status: $student->status,
                rejection_date: $student->rejection_date?->toIso8601String()
            ) {
                public function __construct(
                    public string $public_id,
                    public string $status,
                    public ?string $rejection_date
                ) {}
            }
        );
    }

    /**
     * Helper to extract scalar value from backed enum or string.
     */
    private function resolveValue(mixed $value): string
    {
        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        return (string) $value;
    }
}
