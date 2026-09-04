<?php declare(strict_types=1);

namespace App\Services\Api;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
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
    public function authLoginPost(LoginRequest $LoginRequest): AuthLoginPost200Response|ErrorResponse|ValidationErrorResponse
    {
        $credentials = [
            'email' => $LoginRequest->email,
            'password' => $LoginRequest->password,
        ];

        if (!Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        request()->session()->regenerate();

        /** @var User $user */
        $user = Auth::user();

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
     */
    public function authLogoutPost(): StandardResponse|ErrorResponse
    {
        Auth::guard('web')->logout();

        if (request()->hasSession()) {
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }

        return new StandardResponse(
            'success',
            'Successfully logged out.',
            new class {}
        );
    }

    /**
     * Change Authenticated User Password
     */
    public function authPasswordChangePost(PasswordChangeRequest $PasswordChangeRequest): StandardResponse|ErrorResponse|ValidationErrorResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user) {
            return new ErrorResponse('error', 'Unauthenticated');
        }

        if (!Hash::check($PasswordChangeRequest->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided password does not match your current password.'],
            ]);
        }

        $user->password = Hash::make($PasswordChangeRequest->new_password);
        $user->save();

        return new StandardResponse(
            'success',
            'Password updated successfully.',
            new class {}
        );
    }
}
