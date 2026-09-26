<?php declare(strict_types=1);

namespace App\Jobs;

use App\Models\Fee;
use App\Models\Student;
use App\Models\StudentFee;
use DateTime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AssignDirectStudentsFeeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Fee $fee,
        public array $studentPublicIds,
        public ?DateTime $dueDate = null,
        public ?float $overrideAmountZmw = null,
    ) {}

    public function handle(): void
    {
        Student::query()
            ->whereIn('public_id', $this->studentPublicIds)
            ->chunkById(200, function ($students) {
                foreach ($students as $student) {
                    StudentFee::query()->firstOrCreate(
                        [
                            'student_id' => $student->id,
                            'fee_id' => $this->fee->id,
                        ],
                        [
                            'amount_zmw' => $this->overrideAmountZmw ?? $this->fee->amount_zmw,
                            'amount_usd' => $this->fee->amount_usd,
                        ]
                    );
                }
            });
    }
}
