<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\GeneralNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    /**
     * Send a notification to a single user.
     */
    public static function sendToUser(
        User $user,
        string $title,
        string $body,
        ?string $actionUrl = null,
        string $type = 'info',
        array $extraData = []
    ): void {
        $user->notify(new GeneralNotification($title, $body, $actionUrl, $type, $extraData));
    }

    /**
     * Send a notification to a collection or array of users.
     */
    public static function sendToUsers(
        Collection|array $users,
        string $title,
        string $body,
        ?string $actionUrl = null,
        string $type = 'info',
        array $extraData = []
    ): void {
        if (empty($users) || ($users instanceof Collection && $users->isEmpty())) {
            return;
        }

        Notification::send($users, new GeneralNotification($title, $body, $actionUrl, $type, $extraData));
    }

    /**
     * Send a notification to all users matching a specific role using Eloquent relationships.
     */
    public static function sendToRole(
        string $role,
        string $title,
        string $body,
        ?string $actionUrl = null,
        string $type = 'info',
        array $extraData = []
    ): void {
        $users = match (strtolower($role)) {
            'admin', 'admins' => User::whereHas('admins')->get(),
            'student', 'students' => User::whereHas('students')->get(),
            'lecturer', 'lecturers' => User::whereHas('lecturers')->get(),
            default => collect(),
        };

        if ($users->isNotEmpty()) {
            self::sendToUsers($users, $title, $body, $actionUrl, $type, $extraData);
        }
    }
}
