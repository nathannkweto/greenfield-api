<?php

namespace App\Console\Commands;

use App\Models\AcademicTerm;
use App\Models\Student;
use App\Jobs\ProcessStudentTermTransitionJob;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessTermStartCommand extends Command
{
    protected $signature = 'calendar:process-term-start';
    protected $description = 'Trigger student progression and fee jobs on the 2nd Saturday before term start';

    public function handle(): void
    {
        $today = Carbon::today();

        // Fetch terms starting in the future
        $upcomingTerms = AcademicTerm::where('start_date', '>', $today)->get();

        foreach ($upcomingTerms as $term) {
            $termStart = Carbon::parse($term->start_date);

            // Calculate the 2nd Saturday before start date
            $firstSaturdayBefore = $termStart->isSaturday()
                ? $termStart->copy()->subWeek()
                : $termStart->copy()->previous(Carbon::SATURDAY);

            $secondSaturdayBefore = $firstSaturdayBefore->copy()->subWeek();

            if ($today->isSameDay($secondSaturdayBefore)) {
                $this->info("Dispatching transitions for Term: {$term->term} ({$term->academic_year_id})");

                // Retrieve all active/registered students
                Student::whereIn('status', ['registered', 'active'])
                    ->chunk(200, function ($students) use ($term) {
                        foreach ($students as $student) {
                            ProcessStudentTermTransitionJob::dispatch($student, $term);
                        }
                    });
            }
        }
    }
}
