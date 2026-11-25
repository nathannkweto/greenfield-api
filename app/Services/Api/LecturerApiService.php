<?php declare(strict_types=1);

namespace App\Services\Api;

use App\Exceptions\DuplicateRecordException;
use App\Exceptions\ResourceNotFoundException;
use App\Mail\WelcomeLecturerMail;
use App\Models\Curriculum;
use App\Models\Lecturer;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use OpenAPI\Server\Api\LecturersApiInterface;
use OpenAPI\Server\Model\LecturerRequest;
use OpenAPI\Server\Model\LecturersPublicIdAssignPostRequest;
use OpenAPI\Server\Model\NoContent200;
use OpenAPI\Server\Model\NoContent201;
use Throwable;

class LecturerApiService implements LecturersApiInterface
{
    /**
     * Create Lecturer Profile & User Account
     */
    public function lecturersCreatePost(LecturerRequest $LecturerRequest): NoContent201
    {
        $data = $this->normalizeLecturerRequest($LecturerRequest);
        $this->validateLecturerData($data);

        /** @var School|null $school */
        $school = School::query()
            ->where('public_id', $data['school_public_id'])
            ->first();

        if (!$school) {
            throw new ResourceNotFoundException("School '{$data['school_public_id']}' not found.");
        }

        try {
            DB::transaction(function () use ($school, $data) {
                /** @var User|null $user */
                $user = User::query()
                    ->where('email', $data['email'])
                    ->first();

                $plainPassword = null;

                if ($user) {
                    if (Lecturer::query()->where('user_id', $user->id)->exists()) {
                        throw new DuplicateRecordException(
                            message: 'This user already has a lecturer profile.',
                            errors: ['email' => ['This user is already registered as a lecturer.']]
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

                /** @var Lecturer $lecturer */
                $lecturer = Lecturer::query()->create([
                    'public_id'         => Str::uuid()->toString(),
                    'user_id'           => $user->id,
                    'school_id'         => $school->id,
                    'first_name'        => $data['first_name'],
                    'middle_name'       => $data['middle_name'],
                    'last_name'         => $data['last_name'],
                    'dob'               => $data['dob'],
                    'address'           => $data['address'],
                    'emergency_contact' => $data['emergency_contact'],
                ]);

                if ($plainPassword !== null && $user->email) {
                    DB::afterCommit(function () use ($user, $lecturer, $plainPassword) {
                        try {
                            Mail::to($user->email)->send(new WelcomeLecturerMail($lecturer, $plainPassword));
                        } catch (Throwable $e) {
                            Log::error('Failed to send WelcomeLecturerMail.', [
                                'lecturer_public_id' => $lecturer->public_id,
                                'email'              => $user->email,
                                'exception'          => $e->getMessage(),
                            ]);
                        }
                    });
                }
            });
        } catch (UniqueConstraintViolationException) {
            throw new DuplicateRecordException(
                message: 'A lecturer or user account with the provided email already exists.',
                errors: ['email' => ['This email address is already in use.']]
            );
        }

        return new NoContent201();
    }

    /**
     * Edit Lecturer Profile & User Information
     */
    public function lecturersPublicIdEditPost(string $public_id, LecturerRequest $LecturerRequest): NoContent200
    {
        $publicId = trim($public_id);
        $data = $this->normalizeLecturerRequest($LecturerRequest);
        $this->validateLecturerData($data);

        $lecturer = $this->findLecturerOrFail($publicId, withUser: true);

        /** @var School|null $school */
        $school = School::query()
            ->where('public_id', $data['school_public_id'])
            ->first();

        if (!$school) {
            throw new ResourceNotFoundException("School '{$data['school_public_id']}' not found.");
        }

        if ($lecturer->user) {
            if (User::query()
                ->where('email', $data['email'])
                ->where('id', '!=', $lecturer->user_id)
                ->exists()
            ) {
                throw new DuplicateRecordException(
                    message: "The email '{$data['email']}' is already associated with another user.",
                    errors: ['email' => ['This email address is already in use.']]
                );
            }
        }

        try {
            DB::transaction(function () use ($lecturer, $school, $data) {
                if ($lecturer->user) {
                    $userData = ['email' => $data['email']];

                    if ($data['phone'] !== null) {
                        $userData['phone'] = $data['phone'];
                    }

                    $lecturer->user->update($userData);
                }

                $lecturer->update([
                    'school_id'         => $school->id,
                    'first_name'        => $data['first_name'],
                    'middle_name'       => $data['middle_name'],
                    'last_name'         => $data['last_name'],
                    'dob'               => $data['dob'],
                    'address'           => $data['address'],
                    'emergency_contact' => $data['emergency_contact'],
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            throw new DuplicateRecordException(
                message: 'Another account already uses the provided email address.',
                errors: ['email' => ['This email address is already in use.']]
            );
        }

        return new NoContent200();
    }

    /**
     * Assign Curriculum Courses to Lecturer
     */
    public function lecturersPublicIdAssignPost(
        string $public_id,
        LecturersPublicIdAssignPostRequest $LecturersPublicIdAssignPostRequest
    ): NoContent200 {
        $publicId = trim($public_id);

        $rawIds = array_map(
            static fn ($id) => is_string($id) ? trim($id) : $id,
            $LecturersPublicIdAssignPostRequest->curriculum_public_ids
        );

        Validator::make(
            ['curriculum_public_ids' => $rawIds],
            [
                'curriculum_public_ids'   => ['required', 'array', 'min:1'],
                'curriculum_public_ids.*' => ['required', 'string', 'uuid'],
            ]
        )->validate();

        $lecturer = $this->findLecturerOrFail($publicId);

        /** @var array<int, string> $curriculumIds */
        $curriculumIds = array_values(array_unique($rawIds));

        /** @var array<int, string> $foundIds */
        $foundIds = Curriculum::query()
            ->whereIn('public_id', $curriculumIds)
            ->pluck('public_id')
            ->all();

        $missingIds = array_values(array_diff($curriculumIds, $foundIds));

        if (!empty($missingIds)) {
            throw new ResourceNotFoundException(
                'One or more curriculum records were not found: ' . implode(', ', $missingIds)
            );
        }

        DB::transaction(function () use ($lecturer, $curriculumIds) {
            Curriculum::query()
                ->whereIn('public_id', $curriculumIds)
                ->update(['lecturer_id' => $lecturer->id]);
        });

        return new NoContent200();
    }

    private function findLecturerOrFail(string $publicId, bool $withUser = false): Lecturer
    {
        if (!Str::isUuid($publicId)) {
            throw new ResourceNotFoundException("Lecturer '{$publicId}' not found.");
        }

        $query = Lecturer::query()->where('public_id', $publicId);

        if ($withUser) {
            $query->with('user');
        }

        /** @var Lecturer|null $lecturer */
        $lecturer = $query->first();

        if (!$lecturer) {
            throw new ResourceNotFoundException("Lecturer '{$publicId}' not found.");
        }

        return $lecturer;
    }

    /**
     * @return array<string, string|null>
     */
    private function normalizeLecturerRequest(LecturerRequest $request): array
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
            'school_public_id'  => $clean($request->school_public_id),
            'email'             => $email !== null ? Str::lower($email) : null,
            'phone'             => $clean($request->phone),
            'first_name'        => $clean($request->first_name),
            'middle_name'       => $clean($request->middle_name),
            'last_name'         => $clean($request->last_name),
            'dob'               => $request->dob?->format('Y-m-d'),
            'address'           => $clean($request->address),
            'emergency_contact' => $clean($request->emergency_contact),
        ];
    }

    /**
     * @param array<string, string|null> $data
     */
    private function validateLecturerData(array $data): void
    {
        Validator::make($data, [
            'school_public_id'  => ['required', 'string', 'uuid'],
            'email'             => ['required', 'email', 'max:255'],
            'phone'             => ['nullable', 'string', 'max:50'],
            'first_name'        => ['required', 'string', 'max:255'],
            'middle_name'       => ['nullable', 'string', 'max:255'],
            'last_name'         => ['required', 'string', 'max:255'],
            'dob'               => ['nullable', 'date', 'before:today'],
            'address'           => ['nullable', 'string', 'max:1000'],
            'emergency_contact' => ['nullable', 'string', 'max:255'],
        ])->validate();
    }
}
