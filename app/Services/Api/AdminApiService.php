<?php declare(strict_types=1);

namespace App\Services\Api;

use App\Exceptions\DuplicateRecordException;
use App\Exceptions\ResourceNotFoundException;
use App\Mail\WelcomeAdminMail;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use OpenAPI\Server\Api\AdminsApiInterface;
use OpenAPI\Server\Model\AdminRequest;
use OpenAPI\Server\Model\NoContent200;
use OpenAPI\Server\Model\NoContent201;
use Throwable;

class AdminApiService implements AdminsApiInterface
{
    /**
     * Create Administrator Account
     */
    public function adminsCreatePost(AdminRequest $AdminRequest): NoContent201
    {
        $data = $this->normalizeRequest($AdminRequest);

        Validator::make($data, [
            'user_public_id'  => ['nullable', 'string'],
            'email'           => ['required_without:user_public_id', 'nullable', 'email', 'max:255'],
            'phone'           => ['nullable', 'string', 'max:50'],
            'first_name'      => ['required', 'string', 'max:255'],
            'middle_name'     => ['nullable', 'string', 'max:255'],
            'last_name'       => ['required', 'string', 'max:255'],
            'employee_number' => ['required', 'string', 'max:100'],
            'department'      => ['required', 'string', 'max:255'],
            'position'        => ['required', 'string', 'max:255'],
        ])->validate();

        if (Admin::query()->where('employee_number', $data['employee_number'])->exists()) {
            throw new DuplicateRecordException(
                message: "An administrator with employee number '{$data['employee_number']}' already exists.",
                errors: ['employee_number' => ['This employee number is already in use.']]
            );
        }

        try {
            DB::transaction(function () use ($data) {
                $user = null;
                $plainPassword = null;

                // 1. Resolve explicit user_public_id (strictly fail with 404 if not found)
                if ($data['user_public_id'] !== null) {
                    $user = User::query()
                        ->where('public_id', $data['user_public_id'])
                        ->when(
                            ctype_digit($data['user_public_id']),
                            fn ($q) => $q->orWhere('id', (int) $data['user_public_id'])
                        )
                        ->first();

                    if (!$user) {
                        throw new ResourceNotFoundException("User '{$data['user_public_id']}' not found.");
                    }
                }

                // 2. Otherwise resolve existing user by email
                if (!$user && $data['email'] !== null) {
                    $user = User::query()
                        ->where('email', $data['email'])
                        ->first();
                }

                // 3. Create new user if none exists
                if (!$user) {
                    $plainPassword = Str::random(12);

                    $user = User::query()->create([
                        'public_id' => (string) Str::uuid(),
                        'email'     => $data['email'],
                        'phone'     => $data['phone'],
                        'password'  => Hash::make($plainPassword),
                    ]);
                }

                // 4. Prevent duplicate Admin profiles for the same User
                if (Admin::query()->where('user_id', $user->id)->exists()) {
                    $field = $data['user_public_id'] !== null ? 'user_public_id' : 'email';

                    throw new DuplicateRecordException(
                        message: 'This user already has an administrator account.',
                        errors: [$field => ['This user is already registered as an administrator.']]
                    );
                }

                // 5. Create Admin record
                /** @var Admin $admin */
                $admin = Admin::query()->create([
                    'public_id'       => (string) Str::uuid(),
                    'user_id'         => $user->id,
                    'first_name'      => $data['first_name'],
                    'middle_name'     => $data['middle_name'],
                    'last_name'       => $data['last_name'],
                    'employee_number' => $data['employee_number'],
                    'department'      => $data['department'],
                    'position'        => $data['position'],
                ]);

                // 6. Dispatch welcome credentials only after DB commit
                if ($plainPassword !== null && $user->email) {
                    DB::afterCommit(function () use ($user, $admin, $plainPassword) {
                        try {
                            Mail::to($user->email)->send(new WelcomeAdminMail($admin, $plainPassword));
                        } catch (Throwable $e) {
                            Log::error('Failed to send WelcomeAdminMail.', [
                                'admin_public_id' => $admin->public_id,
                                'email'           => $user->email,
                                'exception'       => $e->getMessage(),
                            ]);
                        }
                    });
                }
            });
        } catch (UniqueConstraintViolationException) {
            throw new DuplicateRecordException(
                message: 'An administrator with the provided email or employee number already exists.'
            );
        }

        return new NoContent201();
    }

    /**
     * Edit Administrator Details
     */
    public function adminsPublicIdEditPost(string $public_id, AdminRequest $AdminRequest): NoContent200
    {
        $publicId = trim($public_id);
        $data = $this->normalizeRequest($AdminRequest);

        Validator::make($data, [
            'email'           => ['nullable', 'email', 'max:255'],
            'phone'           => ['nullable', 'string', 'max:50'],
            'first_name'      => ['required', 'string', 'max:255'],
            'middle_name'     => ['nullable', 'string', 'max:255'],
            'last_name'       => ['required', 'string', 'max:255'],
            'employee_number' => ['required', 'string', 'max:100'],
            'department'      => ['required', 'string', 'max:255'],
            'position'        => ['required', 'string', 'max:255'],
        ])->validate();

        /** @var Admin|null $admin */
        $admin = Admin::query()
            ->with('user')
            ->where('public_id', $publicId)
            ->when(
                ctype_digit($publicId),
                fn ($q) => $q->orWhere('id', (int) $publicId)
            )
            ->first();

        if (!$admin) {
            throw new ResourceNotFoundException("Admin '{$publicId}' not found.");
        }

        if (Admin::query()
            ->where('employee_number', $data['employee_number'])
            ->where('id', '!=', $admin->id)
            ->exists()
        ) {
            throw new DuplicateRecordException(
                message: "An administrator with employee number '{$data['employee_number']}' already exists.",
                errors: ['employee_number' => ['This employee number is already in use.']]
            );
        }

        if ($admin->user && $data['email'] !== null) {
            if (User::query()
                ->where('email', $data['email'])
                ->where('id', '!=', $admin->user_id)
                ->exists()
            ) {
                throw new DuplicateRecordException(
                    message: "The email '{$data['email']}' is already associated with another user.",
                    errors: ['email' => ['This email address is already in use.']]
                );
            }
        }

        try {
            DB::transaction(function () use ($admin, $data) {
                $admin->update([
                    'first_name'      => $data['first_name'],
                    'middle_name'     => $data['middle_name'],
                    'last_name'       => $data['last_name'],
                    'employee_number' => $data['employee_number'],
                    'department'      => $data['department'],
                    'position'        => $data['position'],
                ]);

                if ($admin->user) {
                    $userData = [];

                    if ($data['email'] !== null) {
                        $userData['email'] = $data['email'];
                    }
                    if ($data['phone'] !== null) {
                        $userData['phone'] = $data['phone'];
                    }

                    if (!empty($userData)) {
                        $admin->user->update($userData);
                    }
                }
            });
        } catch (UniqueConstraintViolationException) {
            throw new DuplicateRecordException(
                message: 'Another record already uses the provided email or employee number.'
            );
        }

        return new NoContent200();
    }

    /**
     * @return array<string, string|null>
     */
    private function normalizeRequest(AdminRequest $request): array
    {
        $clean = static function (?string $value): ?string {
            if ($value === null) {
                return null;
            }
            $trimmed = trim($value);
            return $trimmed === '' ? null : $trimmed;
        };

        $email = $clean($request->email ?? null);

        return [
            'user_public_id'  => $clean($request->user_public_id ?? null),
            'email'           => $email !== null ? Str::lower($email) : null,
            'phone'           => $clean($request->phone ?? null),
            'first_name'      => $clean($request->first_name ?? null),
            'middle_name'     => $clean($request->middle_name ?? null),
            'last_name'       => $clean($request->last_name ?? null),
            'employee_number' => $clean($request->employee_number ?? null),
            'department'      => $clean($request->department ?? null),
            'position'        => $clean($request->position ?? null),
        ];
    }
}
