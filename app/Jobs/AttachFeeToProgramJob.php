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
use Illuminate\Support\Facades\DB;

class AttachFeeToProgramJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Fee $fee,
        public array $programPublicIds
    ) {}

    public function handle(): void
    {
        $programs = Program::query()->whereIn('public_id', $this->programPublicIds)->get();

        foreach ($programs as $program) {
            DB::transaction(function () use ($program) {
                // 1. Create or find the program-specific Fee instance
                // This populates feeable_type and feeable_id so program.fees in GraphQL returns it
                $programFee = $program->fees()->firstOrCreate(
                    [
                        'title' => $this->fee->title,
                        'frequency' => $this->fee->frequency,
                    ],
                    [
                        'amount_zmw' => $this->fee->amount_zmw,
                        'amount_usd' => $this->fee->amount_usd,
                        'account_id' => $this->fee->account_id ?? null,
                    ]
                );
            });
        }
    }
}
