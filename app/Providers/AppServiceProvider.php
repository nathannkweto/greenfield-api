<?php

namespace App\Providers;

use App\Models\Applicant;
use App\Models\User;
use App\Observers\ApplicantObserver;
use App\Services\Api\ApplicantApiService;
use App\Services\Api\AuthApiService;
use App\Services\Api\CurriculaApiService;
use App\Services\Api\NotificationsApiService;
use App\Services\Api\PublicApiService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

// OpenAPI Generated Interfaces
use OpenAPI\Server\Api\AdminsApiInterface;
use OpenAPI\Server\Api\AnnouncementsApiInterface;
use OpenAPI\Server\Api\ApplicantsApiInterface;
use OpenAPI\Server\Api\AuthApiInterface;
use OpenAPI\Server\Api\CoursesApiInterface;
use OpenAPI\Server\Api\CurriculaApiInterface;
use OpenAPI\Server\Api\FinanceApiInterface;
use OpenAPI\Server\Api\LecturersApiInterface;
use OpenAPI\Server\Api\NotificationsApiInterface;
use OpenAPI\Server\Api\ProgramsApiInterface;
use OpenAPI\Server\Api\PublicApiInterface;
use OpenAPI\Server\Api\ResultsApiInterface;
use OpenAPI\Server\Api\SchoolsApiInterface;
use OpenAPI\Server\Api\StudentsApiInterface;

// Concrete API Orchestrator Services
use App\Services\Api\AdminApiService;
use App\Services\Api\AnnouncementApiService;
use App\Services\Api\CourseApiService;
use App\Services\Api\FinanceApiService;
use App\Services\Api\LecturerApiService;
use App\Services\Api\ProgramApiService;
use App\Services\Api\ResultApiService;
use App\Services\Api\SchoolApiService;
use App\Services\Api\StudentApiService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind OpenAPI Generated Interfaces to Concrete API Orchestrator Services
        $this->app->bind(ApplicantsApiInterface::class, ApplicantApiService::class);
        $this->app->bind(AdminsApiInterface::class, AdminApiService::class);
        $this->app->bind(NotificationsApiInterface::class, NotificationsApiService::class);
        $this->app->bind(AnnouncementsApiInterface::class, AnnouncementApiService::class);
        $this->app->bind(CoursesApiInterface::class, CourseApiService::class);
        $this->app->bind(FinanceApiInterface::class, FinanceApiService::class);
        $this->app->bind(LecturersApiInterface::class, LecturerApiService::class);
        $this->app->bind(CurriculaApiInterface::class, CurriculaApiService::class);
        $this->app->bind(ProgramsApiInterface::class, ProgramApiService::class);
        $this->app->bind(PublicApiInterface::class, PublicApiService::class);
        $this->app->bind(AuthApiInterface::class, AuthApiService::class);
        $this->app->bind(ResultsApiInterface::class, ResultApiService::class);
        $this->app->bind(SchoolsApiInterface::class, SchoolApiService::class);
        $this->app->bind(StudentsApiInterface::class, StudentApiService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('read:students', function (User $user) {
            // Adjust logic based on user roles or return true for dev
            return $user->hasRole('admin') || $user->hasRole('lecturer');
        });
        // Applicant::observe(ApplicantObserver::class);
    }
}
