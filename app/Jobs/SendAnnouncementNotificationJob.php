<?php declare(strict_types=1);

namespace App\Jobs;

use App\Models\Announcement;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use OpenAPI\Server\Model\AnnouncementRequestTargetLevel;

class SendAnnouncementNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Announcement $announcement) {}

    public function handle(): void
    {
        $targetLevel = $this->announcement->target_level;

        $recipients = match (true) {
            $targetLevel === 'college' || $targetLevel === AnnouncementRequestTargetLevel::COLLEGE->value
            => User::all(),

            $targetLevel === 'school' || $targetLevel === AnnouncementRequestTargetLevel::SCHOOL->value
            => User::query()
                ->whereHas('students.program', function ($query) {
                    $query->where('school_id', $this->announcement->target_id);
                })
                ->orWhereHas('schools', function ($query) {
                    $query->where('id', $this->announcement->target_id);
                })
                ->get(),

            $targetLevel === 'program' || $targetLevel === AnnouncementRequestTargetLevel::PROGRAM->value
            => User::query()
                ->whereHas('students', function ($query) {
                    $query->where('program_id', $this->announcement->target_id);
                })
                ->get(),

            default => collect(),
        };

        if ($recipients->isEmpty()) {
            return;
        }

        NotificationService::sendToUsers(
            users: $recipients,
            title: "Announcement: {$this->announcement->title}",
            body: Str::limit(strip_tags($this->announcement->content), 100),
            actionUrl: "/announcements/{$this->announcement->public_id}",
            type: 'announcement'
        );
    }
}
