<?php declare(strict_types=1);

namespace App\Services\Api;

use App\Mail\WelcomeApplicantMail;
use App\Models\Applicant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use OpenAPI\Server\Api\ApplicantsApiInterface;
use OpenAPI\Server\Model\ApplicantRequest;
use OpenAPI\Server\Model\ApplicantResponse;
use OpenAPI\Server\Model\ApplicantResponseData;
use OpenAPI\Server\Model\ErrorResponse;
use OpenAPI\Server\Model\ValidationErrorResponse;

class ApplicantApiService implements ApplicantsApiInterface
{
    /**
     * Create User account and Applicant profile within a database transaction.
     */
    public function applicantsCreatePost(
        ApplicantRequest $ApplicantRequest
    ): ApplicantResponse | ErrorResponse | ValidationErrorResponse {
        return DB::transaction(function () use ($ApplicantRequest) {
            $randomPassword = Str::random(8);

            $user = User::create([
                'public_id' => (string) Str::uuid(),
                'email'     => $ApplicantRequest->email,
                'phone'     => $ApplicantRequest->phone,
                'password'  => Hash::make($randomPassword),
            ]);

            $applicant = Applicant::create([
                'public_id'    => (string) Str::uuid(),
                'user_id'      => $user->id,
                'first_name'   => $ApplicantRequest->first_name,
                'middle_names' => $ApplicantRequest->middle_names,
                'last_name'    => $ApplicantRequest->last_name,
                'email'        => $ApplicantRequest->email,
                'phone'        => $ApplicantRequest->phone,
            ]);

            // Dispatch welcome email with plain-text password
            Mail::to($applicant->email)->send(
                new WelcomeApplicantMail($applicant, $randomPassword)
            );

            $responseData = new ApplicantResponseData(
                public_id: $applicant->public_id,
                user_public_id: $user->public_id,
                first_name: $applicant->first_name,
                last_name: $applicant->last_name,
                email: $applicant->email,
                phone: $applicant->phone,
                middle_names: $applicant->middle_names
            );

            return new ApplicantResponse(
                status: 'success',
                message: 'Applicant profile created successfully.',
                data: $responseData
            );
        });
    }
}
