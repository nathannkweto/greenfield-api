<?php

namespace App\Services\AcademicCalendar;

use App\Models\Student;
use App\Models\AcademicTerm;

class AcademicProgressionEvaluator
{
    /**
     * Evaluate and update student academic progression.
     * Returns true if the student has graduated.
     */
    public function evaluate(Student $student, AcademicTerm $term): bool
    {
        // 1. Verify if student's intake matches this term
        if (!$this->matchesIntake($student, $term)) {
            return false;
        }

        // 2. Check academic eligibility / performance criteria
        $isEligibleToProgress = $this->checkPerformanceCriteria($student);

        if (!$isEligibleToProgress) {
            // Record repeat/probation status in academic progress table
            $this->recordProgressionFailure($student, $term);
            return false;
        }

        // 3. Handle final year graduation check
        if ($this->isFinalYear($student, $term)) {
            $student->update(['status' => 'graduated']);
            $this->recordGraduationProgress($student, $term);
            return true;
        }

        // 4. Update progression to next level
        $this->promoteToNextYear($student, $term);
        return false;
    }

    protected function matchesIntake(Student $student, AcademicTerm $term): bool
    {
        return $student->intake_term === $term->term;
    }

    protected function checkPerformanceCriteria(Student $student): bool
    {
        // Custom performance criteria (e.g., GPA >= 2.0, no failed core modules)
        return true;
    }

    protected function isFinalYear(Student $student, AcademicTerm $term): bool
    {
        // Example check: compare student current level with program duration
        return $student->current_year >= $student->program->duration_years;
    }

    protected function promoteToNextYear(Student $student, AcademicTerm $term): void
    {
        $student->increment('current_year');

        $student->academicProgressions()->create([
            'academic_term_id' => $term->id,
            'year' => $student->current_year,
            'status' => 'promoted',
        ]);
    }

    protected function recordGraduationProgress(Student $student, AcademicTerm $term): void
    {
        $student->academicProgressions()->create([
            'academic_term_id' => $term->id,
            'year' => $student->current_year,
            'status' => 'graduated',
        ]);
    }

    protected function recordProgressionFailure(Student $student, AcademicTerm $term): void
    {
        $student->academicProgressions()->create([
            'academic_term_id' => $term->id,
            'year' => $student->current_year,
            'status' => 'retained',
        ]);
    }
}
