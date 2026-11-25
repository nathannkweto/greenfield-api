<?php declare(strict_types=1);

namespace App\Services\Api;

use App\Exceptions\DuplicateRecordException;
use App\Mail\WelcomeApplicantMail;
use App\Models\Applicant;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use OpenAPI\Server\Api\ApplicantsApiInterface;
use OpenAPI\Server\Model\ApplicantRequest;
use OpenAPI\Server\Model\ApplicantResponse;
use OpenAPI\Server\Model\ApplicantResponseData;
use OpenAPI\Server\Model\ErrorResponse;
use OpenAPI\Server\Model\ValidationErrorResponse;
use Throwable;

class ApplicantApiService implements ApplicantsApiInterface
{
    /**
     * Create User account (if needed) and Applicant profile within a database transaction.
     */
    public function applicantsCreatePost(
        ApplicantRequest $ApplicantRequest
    ): ApplicantResponse | ErrorResponse | ValidationErrorResponse {
        $data = $this->normalizeApplicantRequest($ApplicantRequest);
        $this->validateApplicantData($data);

        if (Applicant::query()->where('email', $data['email'])->exists()) {
            throw new DuplicateRecordException(
                message: 'An applicant profile with this email address already exists.',
                errors: ['email' => ['This email address is already registered as an applicant.']]
            );
        }

        try {
            [$user, $applicant] = DB::transaction(function () use ($data) {
                /** @var User|null $user */
                $user = User::query()
                    ->where('email', $data['email'])
                    ->first();

                $plainPassword = null;

                if ($user) {
                    if (Applicant::query()->where('user_id', $user->id)->exists()) {
                        throw new DuplicateRecordException(
                            message: 'This user already has an applicant profile.',
                            errors: ['email' => ['This user already has an applicant profile.']]
                        );
                    }
                } else {
                    $plainPassword = Str::random(12);

                    $user = User::query()->create([
                        'public_id' => Str::uuid()->toString(),
                        'email'     => $data['email'],
                        'phone'     => $data['phone'],
                        'password'  => Hash::make($plainPassword),
                    ]);
                }

                /** @var Applicant $applicant */
                $applicant = Applicant::query()->create([
                    'public_id'    => Str::uuid()->toString(),
                    'user_id'      => $user->id,
                    'first_name'   => $data['first_name'],
                    'middle_names' => $data['middle_names'],
                    'last_name'    => $data['last_name'],
                    'email'        => $data['email'],
                    'phone'        => $data['phone'],
                ]);

                if ($plainPassword !== null && $applicant->email) {
                    DB::afterCommit(function () use ($applicant, $plainPassword) {
                        try {
                            Mail::to($applicant->email)->send(
                                new WelcomeApplicantMail($applicant, $plainPassword)
                            );
                        } catch (Throwable $e) {
                            Log::error('Failed to send WelcomeApplicantMail.', [
                                'applicant_public_id' => $applicant->public_id,
                                'email'               => $applicant->email,
                                'exception'           => $e->getMessage(),
                            ]);
                        }
                    });
                }

                return [$user, $applicant];
            });
        } catch (UniqueConstraintViolationException) {
            throw new DuplicateRecordException(
                message: 'An applicant profile with this email address already exists.',
                errors: ['email' => ['This email address is already registered as an applicant.']]
            );
        }

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
    }

    /**
     * @return array<string, string|null>
     */
    private function normalizeApplicantRequest(ApplicantRequest $request): array
    {
        $clean = static function (?string $value): ?string {
            if ($value === null) {
                return null;
            }
            $trimmed = trim($value);
            return $trimmed === '' ? null : $trimmed;
        };

        $email = $clean($request->email);

        return [
            'first_name'   => $clean($request->first_name),
            'middle_names' => $clean($request->middle_names),
            'last_name'    => $clean($request->last_name),
            'email'        => $email !== null ? Str::lower($email) : null,
            'phone'        => $clean($request->phone),
        ];
    }

    /**
     * @param array<string, string|null> $data
     */
    private function validateApplicantData(array $data): void
    {
        Validator::make($data, [
            'first_name'   => ['required', 'string', 'max:255'],
            'middle_names' => ['nullable', 'string', 'max:255'],
            'last_name'    => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'max:255'],
            'phone'        => ['required', 'string', 'max:50'],
        ])->validate();
    }
}
