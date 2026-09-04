<?php declare(strict_types=1);

namespace App\Services\Api;

use App\Enums\StudentStatus;
use App\Models\Program;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\User;
use DateTime;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use OpenAPI\Server\Api\PublicApiInterface;
use OpenAPI\Server\Model\ApplicationResponse;
use OpenAPI\Server\Model\ApplicationResponseData;
use OpenAPI\Server\Model\ValidationErrorResponse;

class PublicApiService implements PublicApiInterface
{
    /**
     * Submit Admission Application
     *
     * @throws \Throwable
     */
    public function applyPost(
        string $program_public_id,
        string $first_name,
        string $last_name,
        string $email,
        string $phone,
        DateTime $dob,
        string $address,
        string $emergency_contact,
        UploadedFile $nrc_file,
        UploadedFile $certificate_file
    ): ApplicationResponse|ValidationErrorResponse {

        if (User::query()->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => ['An account with this email address already exists.'],
            ]);
        }

        // Inside applyPost() in PublicApiService.php

        $studentData = DB::transaction(function () use (
            $program_public_id,
            $first_name,
            $last_name,
            $email,
            $phone,
            $dob,
            $address,
            $emergency_contact,
            $nrc_file,
            $certificate_file
        ) {
            $program = Program::query()->where('public_id', $program_public_id)->first();
            $programId = $program?->id ?? $program_public_id;
            $programName = $program?->name ?? 'a program';

            // Generate application number BEFORE user creation
            $yearShort = date('y');
            $sequence = Student::whereYear('application_date', date('Y'))->count() + 1;
            $applicationNumber = sprintf('AP%s%03d', $yearShort, $sequence);

            /** @var User $user */
            $user = User::query()->create([
                'public_id' => (string) Str::uuid(),
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'password' => Hash::make($applicationNumber),
                'phone' => $phone,
                'dob' => $dob->format('Y-m-d'),
                'address' => $address,
                'emergency_contact' => $emergency_contact,
            ]);

            /** @var Student $student */
            $student = Student::query()->create([
                'public_id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'program_id' => $programId,
                'application_number' => $applicationNumber,
                'status' => StudentStatus::PENDING,
                'application_date' => now(),
                'cgpa' => 0.00,
                'credits_completed' => 0,
            ]);

            $documentFiles = [
                'NRC/Passport' => $nrc_file,
                'High School Certificate' => $certificate_file,
            ];

            foreach ($documentFiles as $docLabel => $file) {
                if ($file && $file->isValid()) {
                    $storedPath = $file->store('student_documents', 'public');

                    StudentDocument::query()->create([
                        'public_id' => (string) Str::uuid(),
                        'student_id' => $student->id,
                        'document_type' => $docLabel,
                        'file_path' => $storedPath,
                        'original_filename' => $file->getClientOriginalName(),
                        'mime_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }

            // Trigger notification ONLY after database transaction successfully commits
            DB::afterCommit(function () use ($first_name, $last_name, $programName, $student) {
                $adminUsers = User::query()->whereHas('admins')->get();

                if ($adminUsers->isNotEmpty()) {
                    \App\Services\NotificationService::sendToUsers(
                        users: $adminUsers,
                        title: 'New Admission Application',
                        body: "{$first_name} {$last_name} submitted an application for {$programName}.",
                        actionUrl: "/admin/students/{$student->public_id}",
                        type: 'admission'
                    );
                }
            });

            return [
                'user_public_id' => $user->public_id,
                'student_public_id' => $student->public_id,
                'student_number' => $student->student_number ?? $student->application_number,
            ];
        });

        $responseData = new ApplicationResponseData(
            $studentData['user_public_id'],
            $studentData['student_public_id'],
            $studentData['student_number']
        );

        return new ApplicationResponse(
            'success',
            'Application submitted successfully.',
            $responseData
        );
    }
}
