<?php declare(strict_types=1);

namespace App\Services\Api;

use App\Jobs\ProcessBatchAcademicReconciliation;
use App\Jobs\ProcessBatchFinancialReconciliation;
use App\Jobs\ProcessBatchStudentRegistration;
use App\Jobs\ProcessCsvStudentImport;
use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\File;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenAPI\Server\Api\StudentsApiInterface;
use OpenAPI\Server\Model\ApplicationRequest;
use OpenAPI\Server\Model\ApplicationResponse;
use OpenAPI\Server\Model\ApplicationResponseData;
use OpenAPI\Server\Model\BatchStudentRegisterRequest;
use OpenAPI\Server\Model\BatchStudentRegisterResponse;
use OpenAPI\Server\Model\BatchStudentRegisterResponseData;
use OpenAPI\Server\Model\DocumentUploadRequest;
use OpenAPI\Server\Model\ErrorResponse;
use OpenAPI\Server\Model\ProgramAcademicReconciliationRequest;
use OpenAPI\Server\Model\ProgramFinancialReconciliationRequest;
use OpenAPI\Server\Model\ReconciliationBatchResponse;
use OpenAPI\Server\Model\ReconciliationBatchResponseData;
use OpenAPI\Server\Model\StandardResponse;
use OpenAPI\Server\Model\StudentAcademicRecordItem;
use OpenAPI\Server\Model\StudentCourseGradeItem;
use OpenAPI\Server\Model\StudentEnrollmentRequest;
use OpenAPI\Server\Model\StudentFinancialBalanceItem;
use OpenAPI\Server\Model\StudentRegisterItem;
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
            $user = null;

            if (!empty($ApplicationRequest->user_public_id)) {
                $user = User::query()
                    ->where('public_id', $ApplicationRequest->user_public_id)
                    ->orWhere('id', $ApplicationRequest->user_public_id)
                    ->first();
            }

            if (!$user) {
                $user = User::query()->firstOrCreate(
                    ['email' => $ApplicationRequest->email],
                    [
                        'public_id' => (string) Str::uuid(),
                        'name' => trim($ApplicationRequest->first_name . ' ' . $ApplicationRequest->last_name),
                        'password' => bcrypt(Str::random(16)),
                    ]
                );
            }

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
                'dob' => $ApplicationRequest->dob?->format('Y-m-d'),
                'address' => $ApplicationRequest->address,
                'emergency_contact' => $ApplicationRequest->emergency_contact,
                'sex' => $this->resolveValue($ApplicationRequest->sex),
                'marital_status' => $this->resolveValue($ApplicationRequest->marital_status),
                'nationality' => $ApplicationRequest->nationality,
                'nrc_number' => $ApplicationRequest->nrc_number,
                'passport_number' => $ApplicationRequest->passport_number,
                'intake' => $this->resolveValue($ApplicationRequest->intake),
                'study_mode' => $this->resolveValue($ApplicationRequest->study_mode),
                'status' => 'Pending',
                'application_date' => now(),
            ]);

            // Map all pre-uploaded document collections
            $fileCollections = array_filter([
                'nrc' => $ApplicationRequest->nrc_file_public_id,
                'passport' => $ApplicationRequest->passport_file_public_id,
                'certificate' => $ApplicationRequest->certificate_file_public_id,
                'deposit_slip' => $ApplicationRequest->deposit_slip_file_public_id,
                'exemption_transcript' => $ApplicationRequest->exemption_transcript_file_public_id,
            ]);

            foreach ($fileCollections as $collection => $fileIdentifier) {
                /** @var File|null $file */
                $file = File::query()
                    ->where('public_id', $fileIdentifier)
                    ->orWhere('id', $fileIdentifier)
                    ->first();

                if ($file) {
                    $student->attachFile($file, $collection);
                }
            }

            // Notify Admin users of the new application
            NotificationService::sendToRole(
                role: 'admin',
                title: 'New Application Received',
                body: "{$student->first_name} {$student->last_name} submitted application {$student->application_number}.",
                actionUrl: "/admin/students/{$student->public_id}",
                type: 'info',
                extraData: [
                    'student_public_id' => $student->public_id,
                    'application_number' => $student->application_number,
                    'program_public_id' => $program->public_id,
                ]
            );

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
     * Batch Register Students (Form Table Method)
     */
    public function programsPublicIdStudentsBatchPost(
        string $public_id,
        BatchStudentRegisterRequest $BatchStudentRegisterRequest
    ): BatchStudentRegisterResponse|ErrorResponse|ValidationErrorResponse {
        /** @var Program|null $program */
        $program = Program::query()
            ->where('public_id', $public_id)
            ->first();

        if (!$program) {
            return new ErrorResponse(
                message: 'Selected program not found.',
                error: 'NOT_FOUND'
            );
        }

        $students = $BatchStudentRegisterRequest->students ?? [];
        if (!is_array($students)) {
            $students = [];
        }

        // Convert hydrated StudentRegisterItem objects or raw payload arrays into uniform arrays
        $studentsData = array_map(function (StudentRegisterItem|array $item) {
            $isObj = $item instanceof StudentRegisterItem;

            $get = function (string $key) use ($item, $isObj) {
                if ($isObj) {
                    return $item->{$key} ?? null;
                }
                return $item[$key] ?? null;
            };

            $dob = $get('dob');
            $dobFormatted = match (true) {
                $dob instanceof \DateTimeInterface => $dob->format('Y-m-d'),
                is_string($dob) => $dob,
                default => null,
            };

            return [
                'first_name' => $get('first_name'),
                'middle_names' => $get('middle_names'),
                'last_name' => $get('last_name'),
                'email' => $get('email'),
                'phone' => $get('phone'),
                'sex' => $this->resolveValue($get('sex')),
                'marital_status' => $this->resolveValue($get('marital_status')),
                'nationality' => $get('nationality') ?? 'Zambian',
                'nrc_number' => $get('nrc_number'),
                'passport_number' => $get('passport_number'),
                'intake' => $this->resolveValue($get('intake')),
                'study_mode' => $this->resolveValue($get('study_mode')),
                'address' => $get('address'),
                'emergency_contact' => $get('emergency_contact'),
                'dob' => $dobFormatted,
                'application_number' => $get('application_number'),
                'admission_number' => $get('admission_number'),
                'student_number' => $get('student_number'),
            ];
        }, $students);

        // Dispatch background processing job
        ProcessBatchStudentRegistration::dispatch($program->id, $studentsData);

        return new BatchStudentRegisterResponse(
            status: 'success',
            message: 'Batch student registration queued for background processing.',
            data: new BatchStudentRegisterResponseData(
                total_processed: count($studentsData),
                successful_count: 0,
                failed_count: 0,
                errors: []
            )
        );
    }

    /**
     * Import Registered Students via CSV File
     */
    public function programsPublicIdStudentsImportCsvPost(
        string $public_id,
        UploadedFile $file
    ): BatchStudentRegisterResponse|ErrorResponse|ValidationErrorResponse {
        /** @var Program|null $program */
        $program = Program::query()
            ->where('public_id', $public_id)
            ->first();

        if (!$program) {
            return new ErrorResponse(
                message: 'Selected program not found.',
                error: 'NOT_FOUND'
            );
        }

        // Save CSV temporarily for queue worker
        $storedPath = $file->store('student_imports');

        // Dispatch background processing job
        ProcessCsvStudentImport::dispatch($program->id, $storedPath);

        return new BatchStudentRegisterResponse(
            status: 'success',
            message: 'CSV student import queued for background processing.',
            data: new BatchStudentRegisterResponseData(
                total_processed: 0,
                successful_count: 0,
                failed_count: 0,
                errors: []
            )
        );
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
        $file = File::query()
            ->where('public_id', $DocumentUploadRequest->file_public_id)
            ->orWhere('id', $DocumentUploadRequest->file_public_id)
            ->first();

        if (!$file) {
            return new ErrorResponse(
                message: 'Referenced file not found.',
                error: 'NOT_FOUND'
            );
        }

        $student->attachFile($file, $DocumentUploadRequest->collection);

        return new StandardResponse(
            status: 'success',
            message: 'Document attached successfully.',
            data: new class(
                file_public_id: $file->public_id,
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
    private function resolveValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        return (string) $value;
    }

    /**
     * Batch Reconcile Student Financial Balances
     */
    public function programsPublicIdFinancialReconciliationPost(
        string $public_id,
        ProgramFinancialReconciliationRequest $ProgramFinancialReconciliationRequest
    ): ReconciliationBatchResponse|ErrorResponse|ValidationErrorResponse {
        /** @var Program|null $program */
        $program = Program::query()
            ->where('public_id', $public_id)
            ->first();

        if (!$program) {
            return new ErrorResponse(
                message: 'Selected program not found.',
                error: 'NOT_FOUND'
            );
        }

        $cohortKey = match (true) {
            method_exists($ProgramFinancialReconciliationRequest, 'getCohortKey') => $ProgramFinancialReconciliationRequest->getCohortKey(),
            isset($ProgramFinancialReconciliationRequest->cohort_key) => $ProgramFinancialReconciliationRequest->cohort_key,
            isset($ProgramFinancialReconciliationRequest->cohortKey) => $ProgramFinancialReconciliationRequest->cohortKey,
            default => '',
        };

        $balances = match (true) {
            method_exists($ProgramFinancialReconciliationRequest, 'getBalances') => $ProgramFinancialReconciliationRequest->getBalances(),
            isset($ProgramFinancialReconciliationRequest->balances) => $ProgramFinancialReconciliationRequest->balances,
            default => [],
        };

        if (!is_array($balances)) {
            $balances = [];
        }

        $balancesData = array_map(function (mixed $item): array {
            $studentId = match (true) {
                is_object($item) && method_exists($item, 'getStudentId') => $item->getStudentId(),
                is_object($item) && isset($item->student_id) => $item->student_id,
                is_object($item) && isset($item->studentId) => $item->studentId,
                is_array($item) => $item['student_id'] ?? $item['studentId'] ?? '',
                default => '',
            };

            $feeBalance = match (true) {
                is_object($item) && method_exists($item, 'getFeeBalance') => $item->getFeeBalance(),
                is_object($item) && isset($item->fee_balance) => $item->fee_balance,
                is_object($item) && isset($item->feeBalance) => $item->feeBalance,
                is_array($item) => $item['fee_balance'] ?? $item['feeBalance'] ?? 0.0,
                default => 0.0,
            };

            return [
                'student_id' => (string) $studentId,
                'fee_balance' => (float) $feeBalance,
            ];
        }, $balances);

        // Queue worker job
        ProcessBatchFinancialReconciliation::dispatch(
            $program->id,
            (string) $cohortKey,
            $balancesData
        );

        return new ReconciliationBatchResponse(
            status: 'success',
            message: 'Financial reconciliation batch queued for background processing.',
            data: new ReconciliationBatchResponseData(
                total_processed: count($balancesData),
                successful_count: 0,
                failed_count: 0,
                errors: []
            )
        );
    }

    /**
     * Batch Reconcile Student Academic Marks and Progress
     */
    public function programsPublicIdAcademicReconciliationPost(
        string $public_id,
        ProgramAcademicReconciliationRequest $ProgramAcademicReconciliationRequest
    ): ReconciliationBatchResponse|ErrorResponse|ValidationErrorResponse {
        /** @var Program|null $program */
        $program = Program::query()
            ->where('public_id', $public_id)
            ->first();

        if (!$program) {
            return new ErrorResponse(
                message: 'Selected program not found.',
                error: 'NOT_FOUND'
            );
        }

        $cohortKey = match (true) {
            method_exists($ProgramAcademicReconciliationRequest, 'getCohortKey') => $ProgramAcademicReconciliationRequest->getCohortKey(),
            isset($ProgramAcademicReconciliationRequest->cohort_key) => $ProgramAcademicReconciliationRequest->cohort_key,
            isset($ProgramAcademicReconciliationRequest->cohortKey) => $ProgramAcademicReconciliationRequest->cohortKey,
            default => '',
        };

        $records = match (true) {
            method_exists($ProgramAcademicReconciliationRequest, 'getAcademicRecords') => $ProgramAcademicReconciliationRequest->getAcademicRecords(),
            isset($ProgramAcademicReconciliationRequest->academic_records) => $ProgramAcademicReconciliationRequest->academic_records,
            isset($ProgramAcademicReconciliationRequest->academicRecords) => $ProgramAcademicReconciliationRequest->academicRecords,
            default => [],
        };

        if (!is_array($records)) {
            $records = [];
        }

        $recordsData = array_map(function (mixed $item): array {
            $studentId = match (true) {
                is_object($item) && method_exists($item, 'getStudentId') => $item->getStudentId(),
                is_object($item) && isset($item->student_id) => $item->student_id,
                is_object($item) && isset($item->studentId) => $item->studentId,
                is_array($item) => $item['student_id'] ?? $item['studentId'] ?? '',
                default => '',
            };

            $rawGrades = match (true) {
                is_object($item) && method_exists($item, 'getGrades') => $item->getGrades(),
                is_object($item) && isset($item->grades) => $item->grades,
                is_array($item) => $item['grades'] ?? [],
                default => [],
            };

            if (!is_array($rawGrades)) {
                $rawGrades = [];
            }

            $gradesData = array_map(function (mixed $grade): array {
                $courseId = match (true) {
                    is_object($grade) && method_exists($grade, 'getCourseId') => $grade->getCourseId(),
                    is_object($grade) && isset($grade->course_id) => $grade->course_id,
                    is_object($grade) && isset($grade->courseId) => $grade->courseId,
                    is_array($grade) => $grade['course_id'] ?? $grade['courseId'] ?? '',
                    default => '',
                };

                $year = match (true) {
                    is_object($grade) && method_exists($grade, 'getYear') => $grade->getYear(),
                    is_object($grade) && isset($grade->year) => $grade->year,
                    is_array($grade) => $grade['year'] ?? 1,
                    default => 1,
                };

                $maxMark = match (true) {
                    is_object($grade) && method_exists($grade, 'getMaxMark') => $grade->getMaxMark(),
                    is_object($grade) && isset($grade->max_mark) => $grade->max_mark,
                    is_object($grade) && isset($grade->maxMark) => $grade->maxMark,
                    is_array($grade) => $grade['max_mark'] ?? $grade['maxMark'] ?? 100.0,
                    default => 100.0,
                };

                $courseCode = match (true) {
                    is_object($grade) && method_exists($grade, 'getCourseCode') => $grade->getCourseCode(),
                    is_object($grade) && isset($grade->course_code) => $grade->course_code,
                    is_object($grade) && isset($grade->courseCode) => $grade->courseCode,
                    is_array($grade) => $grade['course_code'] ?? $grade['courseCode'] ?? null,
                    default => null,
                };

                $courseTitle = match (true) {
                    is_object($grade) && method_exists($grade, 'getCourseTitle') => $grade->getCourseTitle(),
                    is_object($grade) && isset($grade->course_title) => $grade->course_title,
                    is_object($grade) && isset($grade->courseTitle) => $grade->courseTitle,
                    is_array($grade) => $grade['course_title'] ?? $grade['courseTitle'] ?? null,
                    default => null,
                };

                $mark = match (true) {
                    is_object($grade) && method_exists($grade, 'getMark') => $grade->getMark(),
                    is_object($grade) && isset($grade->mark) => $grade->mark,
                    is_array($grade) => $grade['mark'] ?? null,
                    default => null,
                };

                return [
                    'course_id' => (string) $courseId,
                    'year' => (int) $year,
                    'max_mark' => (float) $maxMark,
                    'course_code' => $courseCode !== null ? (string) $courseCode : null,
                    'course_title' => $courseTitle !== null ? (string) $courseTitle : null,
                    'mark' => $mark !== null ? (float) $mark : null,
                ];
            }, $rawGrades);

            return [
                'student_id' => (string) $studentId,
                'grades' => $gradesData,
            ];
        }, $records);

        // Queue worker job
        ProcessBatchAcademicReconciliation::dispatch(
            $program->id,
            (string) $cohortKey,
            $recordsData
        );

        return new ReconciliationBatchResponse(
            status: 'success',
            message: 'Academic reconciliation batch queued for background processing.',
            data: new ReconciliationBatchResponseData(
                total_processed: count($recordsData),
                successful_count: 0,
                failed_count: 0,
                errors: []
            )
        );
    }
}
