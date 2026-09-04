<?php

namespace App\Observers;

use App\Mail\WelcomeApplicantMail;
use App\Models\Applicant;
use Illuminate\Support\Facades\Mail;

class ApplicantObserver
{
    /**
     * Handle the Applicant "created" event.
     */
    public function created(Applicant $applicant): void
    {
        // Mail::to($applicant->email)->queue(new WelcomeApplicantMail($applicant));
    }

    /**
     * Handle the Applicant "updated" event.
     */
    public function updated(Applicant $applicant): void
    {
        //
    }

    /**
     * Handle the Applicant "deleted" event.
     */
    public function deleted(Applicant $applicant): void
    {
        //
    }

    /**
     * Handle the Applicant "restored" event.
     */
    public function restored(Applicant $applicant): void
    {
        //
    }

    /**
     * Handle the Applicant "force deleted" event.
     */
    public function forceDeleted(Applicant $applicant): void
    {
        //
    }
}
