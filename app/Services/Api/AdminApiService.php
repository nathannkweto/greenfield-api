<?php declare(strict_types=1);

namespace App\Services\Api;

use App\Mail\WelcomeAdminMail;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use OpenAPI\Server\Api\AdminsApiInterface;
use OpenAPI\Server\Model\AdminRequest;
use OpenAPI\Server\Model\NoContent200;
use OpenAPI\Server\Model\NoContent201;

class AdminApiService implements AdminsApiInterface
{
    /**
     * Create Administrator Account
     *
     * @param AdminRequest $AdminRequest
     * @return NoContent201
     */
    public function adminsCreatePost(AdminRequest $AdminRequest): NoContent201
    {
        DB::transaction(function () use ($AdminRequest) {
            /** @var User|null $user */
            $user = null;
            $plainPassword = null;

            // 1. Resolve existing user by public_id or ID if provided
            if (!empty($AdminRequest->user_public_id)) {
                $user = User::query()
                    ->where('public_id', $AdminRequest->user_public_id)
                    ->orWhere('id', $AdminRequest->user_public_id)
                    ->first();
            }

            // 2. Resolve existing user by email if not found yet
            if (!$user && !empty($AdminRequest->email)) {
                $user = User::query()
                    ->where('email', $AdminRequest->email)
                    ->first();
            }

            // 3. Create a new user if none exists
            if (!$user) {
                $plainPassword = Str::random(8); // 8-character random password

                $user = User::query()->create([
                    'public_id' => (string) Str::uuid(),
                    'email'     => $AdminRequest->email,
                    'phone'     => $AdminRequest->phone,
                    'password'  => Hash::make($plainPassword),
                ]);
            }

            // 4. Create Admin record
            /** @var Admin $admin */
            $admin = Admin::query()->create([
                'public_id'       => (string) Str::uuid(),
                'user_id'         => $user->id,
                'first_name'      => $AdminRequest->first_name,
                'middle_name'     => $AdminRequest->middle_name,
                'last_name'       => $AdminRequest->last_name,
                'employee_number' => $AdminRequest->employee_number,
                'department'      => $AdminRequest->department,
                'position'        => $AdminRequest->position,
            ]);

            // 5. Send welcome credentials email if password was generated
            if ($plainPassword && $user->email) {
                Mail::to($user->email)->send(new WelcomeAdminMail($admin, $plainPassword));
            }
        });

        return new NoContent201();
    }

    /**
     * Edit Administrator Details
     *
     * @param string $public_id
     * @param AdminRequest $AdminRequest
     * @return NoContent200
     */
    public function adminsPublicIdEditPost(string $public_id, AdminRequest $AdminRequest): NoContent200
    {
        DB::transaction(function () use ($public_id, $AdminRequest) {
            /** @var Admin $admin */
            $admin = Admin::query()
                ->where('public_id', $public_id)
                ->orWhere('id', $public_id)
                ->firstOrFail();

            $admin->update([
                'first_name'      => $AdminRequest->first_name,
                'middle_name'     => $AdminRequest->middle_name,
                'last_name'       => $AdminRequest->last_name,
                'employee_number' => $AdminRequest->employee_number,
                'department'      => $AdminRequest->department,
                'position'        => $AdminRequest->position,
            ]);

            if ($admin->user) {
                $userData = array_filter([
                    'email' => $AdminRequest->email,
                    'phone' => $AdminRequest->phone,
                ]);

                if (!empty($userData)) {
                    $admin->user->update($userData);
                }
            }
        });

        return new NoContent200();
    }
}
