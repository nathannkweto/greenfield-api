<?php

namespace App\Jobs;

use App\Models\Student;
use App\Models\AcademicTerm;
use App\Services\AcademicCalendar\AcademicProgressionEvaluator;
use App\Services\AcademicCalendar\TermFeeProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessStudentTermTransitionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Student $student,
        public AcademicTerm $term
    ) {}

    public function handle(
        AcademicProgressionEvaluator $progressionEvaluator,
        TermFeeProcessor $feeProcessor
    ): void {
        DB::transaction(function () use ($progressionEvaluator, $feeProcessor) {
            // 1. Process Academic Progression
            $hasGraduated = $progressionEvaluator->evaluate($this->student, $this->term);

            // 2. Process Financials (Skip graduating or non-registered students)
            if (!$hasGraduated && $this->student->status === 'registered') {
                $feeProcessor->process($this->student, $this->term);
            }
        });
    }
}
