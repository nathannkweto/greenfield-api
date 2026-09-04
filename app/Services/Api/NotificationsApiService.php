<?php declare(strict_types=1);

namespace App\Services\Api;

use App\Models\UserFcmToken;
use Illuminate\Support\Facades\Auth;
use OpenAPI\Server\Api\NotificationsApiInterface;
use OpenAPI\Server\Model\FcmTokenRequest;
use OpenAPI\Server\Model\StandardResponse;
use OpenAPI\Server\Model\ErrorResponse;
use OpenAPI\Server\Model\ValidationErrorResponse;

class NotificationsApiService implements NotificationsApiInterface
{
    /**
     * Register FCM Device Token
     *
     * @param FcmTokenRequest $FcmTokenRequest
     * @return StandardResponse|ErrorResponse|ValidationErrorResponse
     */
    public function fcmTokenPost(
        FcmTokenRequest $FcmTokenRequest,
    ): StandardResponse|ErrorResponse|ValidationErrorResponse {
        $user = Auth::user();

        // 1. Guard against missing auth user
        if (!$user) {
            return new ErrorResponse(
                message: 'Unauthenticated user session.',
                error: 'Unauthorized'
            );
        }

        // Access the public property directly
        $token = $FcmTokenRequest->token;

        // 2. Guard against empty token payload
        if (empty($token)) {
            return new ValidationErrorResponse(
                message: 'Device FCM token is required.',
                errors: [
                    'token' => ['The device token field must not be empty.']
                ]
            );
        }

        // 3. Store or re-assign token using explicit query builder to fix IDE inspection warnings
        UserFcmToken::query()->updateOrCreate(
            ['token' => $token],
            ['user_id' => $user->id]
        );

        // 4. Return new StandardResponse passing required constructor arguments
        return new StandardResponse(
            status: 'success',
            message: 'FCM token registered successfully.',
            data: new class {}
        );
    }
}
