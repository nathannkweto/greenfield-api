<?php declare(strict_types=1);

namespace App\Jobs;

use App\Models\Fee;
use App\Models\Program;
use App\Models\Student;
use App\Models\StudentFee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AssignBulkFeeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Fee $fee,
        public array $filters
    ) {}

    public function handle(): void
    {
        $query = Student::query();

        // Bypass filters if student numbers are explicit
        if (!empty($this->filters['student_numbers'])) {
            $query->whereIn('student_number', $this->filters['student_numbers']);
        } else {
            if (!empty($this->filters['program_public_id'])) {
                $program = Program::query()->where('public_id', $this->filters['program_public_id'])->first();
                if ($program) {
                    $query->where('program_id', $program->id);
                }
            }

            if (!empty($this->filters['intake'])) {
                $query->where('intake', $this->filters['intake']);
            }

            if (!empty($this->filters['registration_year'])) {
                $query->whereYear('application_date', $this->filters['registration_year']);
            }

            if (!empty($this->filters['study_mode'])) {
                $query->where('study_mode', $this->filters['study_mode']);
            }

            if (!empty($this->filters['status'])) {
                $query->where('status', $this->filters['status']);
            }
        }

        $query->chunkById(200, function ($students) {
            foreach ($students as $student) {
                StudentFee::query()->firstOrCreate(
                    [
                        'student_id' => $student->id,
                        'fee_id' => $this->fee->id,
                    ],
                    [
                        'amount_zmw' => $this->fee->amount_zmw,
                        'amount_usd' => $this->fee->amount_usd,
                    ]
                );
            }
        });
    }
}
