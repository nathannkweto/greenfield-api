<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenAPI\Server\Http\Controllers\AdminsController;
use OpenAPI\Server\Http\Controllers\AnnouncementsController;
use OpenAPI\Server\Http\Controllers\AuthController;
use OpenAPI\Server\Http\Controllers\CoursesController;
use OpenAPI\Server\Http\Controllers\FinanceController;
use OpenAPI\Server\Http\Controllers\LecturersController;
use OpenAPI\Server\Http\Controllers\ProgramsController;
use OpenAPI\Server\Http\Controllers\PublicController;
use OpenAPI\Server\Http\Controllers\ResultsController;
use OpenAPI\Server\Http\Controllers\SchoolsController;
use OpenAPI\Server\Http\Controllers\StudentsController;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES (No Authentication Required)
|--------------------------------------------------------------------------
*/

Route::GET('/v1/sanctum/csrf-cookie', [AuthController::class, 'sanctumCsrfCookieGet'])->name('publicAuth.sanctum.csrf.cookie.get');
Route::POST('/v1/auth/login', [AuthController::class, 'authLoginPost'])->name('publicAuth.auth.login.post');
Route::POST('/v1/applicants/create', [\OpenAPI\Server\Http\Controllers\ApplicantsController::class, 'applicantsCreatePost'])->name('applicants.applicants.create.post');

/*
|--------------------------------------------------------------------------
| PROTECTED ROUTES (Requires Active Session / Token)
|--------------------------------------------------------------------------
*/

 Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | ADMIN-ONLY ROUTES
    |--------------------------------------------------------------------------
    | System configuration, finance, enrollments, and user management.
    */
     Route::POST('/v1/files/upload', [\OpenAPI\Server\Http\Controllers\FilesController::class, 'filesUploadPost'])->name('files.files.upload.post');
     Route::POST('/v1/apply', [\OpenAPI\Server\Http\Controllers\StudentsController::class, 'applyPost'])->name('students.apply.post');

     Route::middleware('role:admin')->group(function () {
        // Admins
        Route::POST('/v1/admins/create', [AdminsController::class, 'adminsCreatePost'])->name('admins.admins.create.post');
        Route::POST('/v1/admins/{public_id}/edit', [AdminsController::class, 'adminsPublicIdEditPost'])->name('admins.admins.public.id.edit.post');

        // Schools & Programs
        Route::POST('/v1/schools/create', [SchoolsController::class, 'schoolsCreatePost'])->name('schools.schools.create.post');
        Route::POST('/v1/schools/{public_id}/edit', [SchoolsController::class, 'schoolsPublicIdEditPost'])->name('schools.schools.public.id.edit.post');
        Route::POST('/v1/programs/create', [ProgramsController::class, 'programsCreatePost'])->name('programs.programs.create.post');
        Route::POST('/v1/programs/{public_id}/edit', [ProgramsController::class, 'programsPublicIdEditPost'])->name('programs.programs.public.id.edit.post');
        Route::POST('/v1/programs/{public_id}/curriculum', [\OpenAPI\Server\Http\Controllers\ProgramsController::class, 'programsPublicIdCurriculumPost'])->name('programs.programs.public.id.curriculum.post');
        Route::POST('/v1/programs/{public_id}/requirements', [\OpenAPI\Server\Http\Controllers\ProgramsController::class, 'programsPublicIdRequirementsPost'])->name('programs.programs.public.id.requirements.post');


     // Course Catalog (Creation and root editing)
        Route::POST('/v1/courses/create', [CoursesController::class, 'coursesCreatePost'])->name('courses.courses.create.post');
        Route::POST('/v1/courses/{public_id}/edit', [CoursesController::class, 'coursesPublicIdEditPost'])->name('courses.courses.public.id.edit.post');

        // Lecturers Management
        Route::POST('/v1/lecturers/create', [LecturersController::class, 'lecturersCreatePost'])->name('lecturers.lecturers.create.post');
        Route::POST('/v1/lecturers/{public_id}/edit', [LecturersController::class, 'lecturersPublicIdEditPost'])->name('lecturers.lecturers.public.id.edit.post');
        Route::POST('/v1/lecturers/{public_id}/assign', [LecturersController::class, 'lecturersPublicIdAssignPost'])->name('lecturers.lecturers.public.id.assign.post');

        // Students Management
        Route::POST('/v1/students/{public_id}/register', [\OpenAPI\Server\Http\Controllers\StudentsController::class, 'studentsPublicIdRegisterPost'])->name('students.students.public.id.register.post');
        Route::POST('/v1/students/{public_id}/admit', [\OpenAPI\Server\Http\Controllers\StudentsController::class, 'studentsPublicIdAdmitPost'])->name('students.students.public.id.admit.post');
        Route::POST('/v1/students/{public_id}/reject', [\OpenAPI\Server\Http\Controllers\StudentsController::class, 'studentsPublicIdRejectPost'])->name('students.students.public.id.reject.post');

        // Finance
//        Route::POST('/api/v1/finance/fee/create', [FinanceController::class, 'financeFeeCreatePost'])->name('finance.finance.fee.create.post');
//        Route::POST('/api/v1/finance/fee/{public_id}/edit', [FinanceController::class, 'financeFeePublicIdEditPost'])->name('finance.finance.fee.public.id.edit.post');
//        Route::POST('/api/v1/finance/transaction/create', [FinanceController::class, 'financeTransactionCreatePost'])->name('finance.finance.transaction.create.post');
//        Route::POST('/api/v1/finance/transaction/{public_id}/reverse', [FinanceController::class, 'financeTransactionPublicIdReversePost'])->name('finance.finance.transaction.public.id.reverse.post');

        // Announcements
        Route::POST('/v1/announcements/create', [AnnouncementsController::class, 'announcementsCreatePost'])->name('announcements.announcements.create.post');
        Route::POST('/v1/announcements/{public_id}/edit', [AnnouncementsController::class, 'announcementsPublicIdEditPost'])->name('announcements.announcements.public.id.edit.post');
    });

    /*
    |--------------------------------------------------------------------------
    | ACADEMIC MANAGEMENT (Admins & Lecturers)
    |--------------------------------------------------------------------------
    | Managing course materials, assessments, and grading.
    */
    Route::middleware('role:admin,lecturer')->group(function () {

        // Course Materials
        Route::POST('/v1/courses/material/create', [CoursesController::class, 'coursesMaterialCreatePost'])->name('courses.courses.material.create.post');
        Route::POST('/v1/courses/material/{public_id}/edit', [CoursesController::class, 'coursesMaterialPublicIdEditPost'])->name('courses.courses.material.public.id.edit.post');

        // Assessments & Grades
        Route::POST('/v1/curricula/{public_id}/assessments/create', [\OpenAPI\Server\Http\Controllers\CurriculaController::class, 'curriculaPublicIdAssessmentsCreatePost'])->name('curricula.curricula.public.id.assessments.create.post');
    //    Route::POST('/api/v1/courses/assessments/{public_id}/edit', [CoursesController::class, 'coursesAssessmentsPublicIdEditPost'])->name('courses.courses.assessments.public.id.edit.post');
    //    Route::POST('/api/v1/courses/assessments/{public_id}/grades', [CoursesController::class, 'coursesAssessmentsPublicIdGradesPost'])->name('courses.courses.assessments.public.id.grades.post');

        // Results
    //    Route::POST('/api/v1/results/{public_id}/publish', [ResultsController::class, 'resultsPublicIdPublishPost'])->name('results.results.public.id.publish.post');

    });

     Route::POST('/v1/auth/logout', [AuthController::class, 'authLogoutPost'])->name('publicAuth.auth.logout.post');
     Route::POST('/v1/fcm-token', [\OpenAPI\Server\Http\Controllers\NotificationsController::class, 'fcmTokenPost'])->name('notifications.fcm.token.post');




 });
