<?php declare(strict_types=1);

namespace App\Services\Api;

use App\Exceptions\Auth\AccountSuspendedException;
use App\Exceptions\Auth\InvalidCredentialsException;
use App\Exceptions\Auth\TooManyLoginAttemptsException;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use OpenAPI\Server\Api\AuthApiInterface;
use OpenAPI\Server\Model\AuthLoginPost200Response;
use OpenAPI\Server\Model\AuthLoginPost200ResponseUser;
use OpenAPI\Server\Model\ErrorResponse;
use OpenAPI\Server\Model\LoginRequest;
use OpenAPI\Server\Model\NoContent204;
use OpenAPI\Server\Model\PasswordChangeRequest;
use OpenAPI\Server\Model\StandardResponse;
use OpenAPI\Server\Model\ValidationErrorResponse;

class AuthApiService implements AuthApiInterface
{
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_SECONDS = 60;

    /**
     * Initialize CSRF Protection
     */
    public function sanctumCsrfCookieGet(): NoContent204
    {
        return new NoContent204();
    }

    /**
     * User Authentication
     */
    public function authLoginPost(
        LoginRequest $LoginRequest
    ): AuthLoginPost200Response|ErrorResponse|ValidationErrorResponse {
        $email = Str::lower(trim($LoginRequest->email));

        Validator::make(
            ['email' => $email, 'password' => $LoginRequest->password],
            [
                'email' => ['required', 'email'],
                'password' => ['required', 'string'],
            ]
        )->validate();

        $throttleKey = $email . '|' . request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_LOGIN_ATTEMPTS)) {
            throw new TooManyLoginAttemptsException(
                RateLimiter::availableIn($throttleKey)
            );
        }

        if (!Auth::attempt(['email' => $email, 'password' => $LoginRequest->password])) {
            RateLimiter::hit($throttleKey, self::LOCKOUT_SECONDS);
            throw new InvalidCredentialsException();
        }

        RateLimiter::clear($throttleKey);

        if (request()->hasSession()) {
            request()->session()->regenerate();
        }

        /** @var User $user */
        $user = Auth::user();

        $roles = $this->resolveUserRoles($user);

        // Block login if user is strictly a student and all student profiles are suspended
        if ($roles === ['student'] && !$user->students()->where('status', '!=', 'Suspended')->exists()) {
            $this->performLogout($user);
            throw new AccountSuspendedException();
        }

        $userDto = new AuthLoginPost200ResponseUser(
            (string) ($user->public_id ?? $user->id),
            $user->email,
            $roles
        );

        return new AuthLoginPost200Response(
            'success',
            'Authenticated successfully.',
            $userDto
        );
    }

    /**
     * User Logout
     *
     * @throws AuthenticationException
     */
    public function authLogoutPost(): StandardResponse|ErrorResponse
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user) {
            throw new AuthenticationException();
        }

        $this->performLogout($user);

        return new StandardResponse(
            'success',
            'Successfully logged out.',
            new class {}
        );
    }

    /**
     * Change Authenticated User Password
     *
     * @throws AuthenticationException
     */
    public function authPasswordChangePost(
        PasswordChangeRequest $PasswordChangeRequest
    ): StandardResponse|ErrorResponse|ValidationErrorResponse {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user) {
            throw new AuthenticationException();
        }

        Validator::make(
            [
                'current_password' => $PasswordChangeRequest->current_password,
                'new_password' => $PasswordChangeRequest->new_password,
                'new_password_confirmation' => $PasswordChangeRequest->new_password_confirmation,
            ],
            [
                'current_password' => ['required', 'string'],
                'new_password' => [
                    'required',
                    'string',
                    'confirmed',
                    'different:current_password',
                    Password::min(8),
                ],
            ]
        )->validate();

        if (!Hash::check($PasswordChangeRequest->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided password does not match your current password.'],
            ]);
        }

        $user->password = Hash::make($PasswordChangeRequest->new_password);
        $user->setRememberToken(Str::random(60));
        $user->save();

        return new StandardResponse(
            'success',
            'Password updated successfully.',
            new class {}
        );
    }

    /**
     * @return array<int, string>
     */
    private function resolveUserRoles(User $user): array
    {
        $roles = [];

        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            $roles[] = 'admin';
        }
        if (method_exists($user, 'isLecturer') && $user->isLecturer()) {
            $roles[] = 'lecturer';
        }
        if (method_exists($user, 'isStudent') && $user->isStudent()) {
            $roles[] = 'student';
        }
        if (method_exists($user, 'isApplicant') && $user->isApplicant()) {
            $roles[] = 'applicant';
        }

        return $roles;
    }

    private function performLogout(User $user): void
    {
        if (method_exists($user, 'currentAccessToken')) {
            $token = $user->currentAccessToken();
            if ($token instanceof PersonalAccessToken) {
                $token->delete();
            }
        }

        Auth::guard('web')->logout();

        if (request()->hasSession()) {
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }
    }
}
