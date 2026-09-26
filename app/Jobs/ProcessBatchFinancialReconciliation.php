<?php declare(strict_types=1);

namespace App\Jobs;

use App\Models\Program;
use App\Models\Student;
use App\Models\StudentFee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessBatchFinancialReconciliation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param array<int, array{student_id: string, fee_balance: float}> $balances
     */
    public function __construct(
        public int $programId,
        public string $cohortKey,
        public array $balances
    ) {}

    public function handle(): void
    {
        $program = Program::find($this->programId);
        if (!$program) {
            Log::error("ProcessBatchFinancialReconciliation: Program ID {$this->programId} not found.");
            return;
        }

        foreach ($this->balances as $item) {
            try {
                DB::transaction(function () use ($item) {
                    $student = Student::query()
                        ->where('public_id', $item['student_id'])
                        ->orWhere('id', $item['student_id'])
                        ->first();

                    if (!$student) {
                        Log::warning("ProcessBatchFinancialReconciliation: Student {$item['student_id']} not found.");
                        return;
                    }

                    StudentFee::updateOrCreate(
                        [
                            'student_id' => $student->id,
                            'fee_id' => null,
                        ],
                        [
                            'amount_zmw' => $item['fee_balance'],
                        ]
                    );
                });
            } catch (Throwable $e) {
                Log::error("ProcessBatchFinancialReconciliation error for student {$item['student_id']}: " . $e->getMessage());
            }
        }
    }
}
