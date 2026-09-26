<?php

namespace App\Services\AcademicCalendar;

use App\Models\Student;
use App\Models\AcademicTerm;
use App\Models\StudentFee;
use Illuminate\Support\Facades\DB;

class TermFeeProcessor
{
    public function process(Student $student, AcademicTerm $term): void
    {
        // Calculate new term fees from program and term structures
        $newFeeAmount = $this->calculateTermFees($student, $term);

        if ($newFeeAmount <= 0) {
            return;
        }

        // 1. Create the Student Fee entry
        $studentFee = StudentFee::create([
            'student_id' => $student->id,
            'academic_term_id' => $term->id,
            'amount' => $newFeeAmount,
            'paid_amount' => 0.00,
            'balance' => $newFeeAmount,
            'status' => 'unpaid',
        ]);

        // 2. Check for advance/credit balance and offset new fees
        $this->applyAdvancePayments($student, $studentFee);
    }

    protected function calculateTermFees(Student $student, AcademicTerm $term): float
    {
        // Fetch term fee applicable to student's program
        return (float) $student->program->term_fee_amount;
    }

    protected function applyAdvancePayments(Student $student, StudentFee $studentFee): void
    {
        $advanceCredit = $student->advance_payment_balance;

        if ($advanceCredit <= 0) {
            return;
        }

        if ($advanceCredit >= $studentFee->balance) {
            // Credit covers the entire new fee
            $remainingCredit = $advanceCredit - $studentFee->balance;

            $studentFee->update([
                'paid_amount' => $studentFee->amount,
                'balance' => 0.00,
                'status' => 'paid',
            ]);

            $student->update(['advance_payment_balance' => $remainingCredit]);
        } else {
            // Partial coverage
            $studentFee->update([
                'paid_amount' => $advanceCredit,
                'balance' => $studentFee->amount - $advanceCredit,
                'status' => 'partial',
            ]);

            $student->update(['advance_payment_balance' => 0.00]);
        }
    }
}
