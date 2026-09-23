<?php declare(strict_types=1);

namespace App\Jobs;

use App\Models\Assessment;
use App\Models\AssessmentResult;
use App\Models\Course;
use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ProcessBatchAcademicReconciliation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param array<int, array{
     *     student_id: string,
     *     grades: array<int, array{
     *         course_id: string,
     *         year: int,
     *         max_mark: float,
     *         course_code: ?string,
     *         course_title: ?string,
     *         mark: ?float
     *     }>
     * }> $academicRecords
     */
    public function __construct(
        public int $programId,
        public string $cohortKey,
        public array $academicRecords
    ) {}

    public function handle(): void
    {
        $program = Program::find($this->programId);
        if (!$program) {
            Log::error("ProcessBatchAcademicReconciliation: Program ID {$this->programId} not found.");
            return;
        }

        foreach ($this->academicRecords as $record) {
            try {
                DB::transaction(function () use ($record, $program) {
                    $student = Student::query()
                        ->where('public_id', $record['student_id'])
                        ->orWhere('id', $record['student_id'])
                        ->first();

                    if (!$student) {
                        Log::warning("ProcessBatchAcademicReconciliation: Student {$record['student_id']} not found.");
                        return;
                    }

                    foreach ($record['grades'] as $grade) {
                        // Resolve course
                        $course = Course::query()
                            ->where('public_id', $grade['course_id'])
                            ->orWhere('id', $grade['course_id'])
                            ->when($grade['course_code'], fn($q) => $q->orWhere('code', $grade['course_code']))
                            ->first();

                        if (!$course) {
                            Log::warning("ProcessBatchAcademicReconciliation: Course {$grade['course_id']} not found.");
                            continue;
                        }

                        // Resolve or create Curriculum entry
                        $curriculum = Curriculum::firstOrCreate(
                            [
                                'program_id' => $program->id,
                                'course_id' => $course->id,
                                'year' => $grade['year'],
                            ],
                            [
                                'public_id' => (string) Str::uuid(),
                            ]
                        );

                        // Ensure student course enrollment exists
                        $enrollment = Enrollment::firstOrCreate(
                            [
                                'student_id' => $student->id,
                                'curriculum_id' => $curriculum->id,
                            ],
                            [
                                'public_id' => (string) Str::uuid(),
                                'status' => 'Completed',
                                'completion_date' => now(),
                            ]
                        );

                        // Record mark if provided
                        if ($grade['mark'] !== null) {
                            $assessment = Assessment::firstOrCreate(
                                [
                                    'curriculum_id' => $curriculum->id,
                                    'type' => 'End of Term',
                                    'title' => "Reconciliation Grade - {$this->cohortKey}",
                                ],
                                [
                                    'public_id' => (string) Str::uuid(),
                                    'weight_percentage' => 100.00,
                                    'max_score' => $grade['max_mark'] ?? 100.00,
                                ]
                            );

                            AssessmentResult::updateOrCreate(
                                [
                                    'assessment_id' => $assessment->id,
                                    'student_id' => $student->id,
                                ],
                                [
                                    'public_id' => (string) Str::uuid(),
                                    'score' => $grade['mark'],
                                    'is_published' => true,
                                ]
                            );
                        }
                    }
                });
            } catch (Throwable $e) {
                Log::error("ProcessBatchAcademicReconciliation error for student {$record['student_id']}: " . $e->getMessage());
            }
        }
    }
}
