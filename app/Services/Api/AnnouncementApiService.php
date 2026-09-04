<?php declare(strict_types=1);

namespace App\Services\Api;

use App\Jobs\SendAnnouncementNotificationJob;
use App\Models\Announcement;
use App\Models\File;
use App\Models\Program;
use App\Models\School;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenAPI\Server\Api\AnnouncementsApiInterface;
use OpenAPI\Server\Model\AnnouncementRequestJsonType;
use OpenAPI\Server\Model\AnnouncementRequestTargetLevel;
use OpenAPI\Server\Model\AnnouncementRequestType;
use OpenAPI\Server\Model\ErrorResponse;
use OpenAPI\Server\Model\StandardResponse;
use OpenAPI\Server\Model\ValidationErrorResponse;

class AnnouncementApiService implements AnnouncementsApiInterface
{
    /**
     * Create Broadcast Announcement
     */
    public function announcementsCreatePost(
        string $title,
        string $content,
        AnnouncementRequestType|AnnouncementRequestJsonType $type,
        AnnouncementRequestTargetLevel $target_level,
        string $target_name,
        string $author,
        ?string $target_public_id,
        ?UploadedFile $attachment,
    ): StandardResponse|ErrorResponse|ValidationErrorResponse {
        // Resolve foreign key for Target Level entity
        $targetId = $this->resolveTargetId($target_level, $target_public_id);
        if ($target_level !== AnnouncementRequestTargetLevel::COLLEGE && $target_public_id !== null && $targetId === null) {
            return new ValidationErrorResponse(
                message: 'Invalid target reference.',
                errors: ['target_public_id' => ["No {$target_level->value} found matching the provided UUID."]]
            );
        }

        try {
            return DB::transaction(function () use (
                $title,
                $content,
                $type,
                $target_level,
                $target_name,
                $author,
                $target_public_id,
                $attachment,
                $targetId
            ) {
                $attachmentId = null;

                if ($attachment !== null) {
                    $attachmentId = $this->storeAttachment($attachment);
                }

                $announcement = Announcement::create([
                    'public_id' => (string) Str::uuid(),
                    'title' => $title,
                    'content' => $content,
                    'type' => $type->value,
                    'target_level' => $target_level->value,
                    'target_id' => $targetId,
                    'target_name' => $target_name,
                    'author' => $author,
                    'author_id' => Auth::id(),
                    'attachment_id' => $attachmentId,
                ]);

                // Dispatch Push Notification Job AFTER DB Transaction Commits
                DB::afterCommit(function () use ($announcement) {
                    SendAnnouncementNotificationJob::dispatch($announcement);
                });

                return new StandardResponse(
                    status: 'success',
                    message: 'Announcement published successfully.',
                    data: new class($announcement->public_id, $announcement->created_at?->toIso8601String()) {
                        public function __construct(
                            public string $public_id,
                            public ?string $created_at
                        ) {}
                    }
                );
            });
        } catch (\Throwable $e) {
            Log::error('Failed to create announcement: ' . $e->getMessage(), ['exception' => $e]);

            return new ErrorResponse(
                message: 'Failed to create announcement due to a server error.',
                error: 'INTERNAL_SERVER_ERROR'
            );
        }
    }

    /**
     * Edit Announcement
     */
    public function announcementsPublicIdEditPost(
        string $public_id,
        string $title,
        string $content,
        AnnouncementRequestType|AnnouncementRequestJsonType $type,
        AnnouncementRequestTargetLevel $target_level,
        string $target_name,
        string $author,
        ?string $target_public_id,
        ?UploadedFile $attachment,
    ): StandardResponse|ErrorResponse|ValidationErrorResponse {
        $announcement = Announcement::where('public_id', $public_id)->first();

        if (!$announcement) {
            return new ErrorResponse(
                message: 'Announcement not found.',
                error: 'NOT_FOUND'
            );
        }

        $targetId = $this->resolveTargetId($target_level, $target_public_id);
        if ($target_level !== AnnouncementRequestTargetLevel::COLLEGE && $target_public_id !== null && $targetId === null) {
            return new ValidationErrorResponse(
                message: 'Invalid target reference.',
                errors: ['target_public_id' => ["No {$target_level->value} found matching the provided UUID."]]
            );
        }

        try {
            return DB::transaction(function () use (
                $announcement,
                $title,
                $content,
                $type,
                $target_level,
                $target_name,
                $author,
                $attachment,
                $targetId
            ) {
                if ($attachment !== null) {
                    // Delete previous physical file and model if replaced
                    if ($announcement->file) {
                        Storage::disk('public')->delete($announcement->file->file_path);
                        $announcement->file->delete();
                    }

                    $announcement->attachment_id = $this->storeAttachment($attachment);
                }

                $announcement->update([
                    'title' => $title,
                    'content' => $content,
                    'type' => $type->value,
                    'target_level' => $target_level->value,
                    'target_id' => $targetId,
                    'target_name' => $target_name,
                    'author' => $author,
                    'attachment_id' => $announcement->attachment_id,
                ]);

                return new StandardResponse(
                    status: 'success',
                    message: 'Announcement updated successfully.',
                    data: new class($announcement->public_id, $announcement->updated_at?->toIso8601String()) {
                        public function __construct(
                            public string $public_id,
                            public ?string $updated_at
                        ) {}
                    }
                );
            });
        } catch (\Throwable $e) {
            Log::error('Failed to update announcement: ' . $e->getMessage(), [
                'public_id' => $public_id,
                'exception' => $e,
            ]);

            return new ErrorResponse(
                message: 'Failed to update announcement due to a server error.',
                error: 'INTERNAL_SERVER_ERROR'
            );
        }
    }

    /**
     * Resolve target level UUID to internal target_id
     */
    private function resolveTargetId(AnnouncementRequestTargetLevel $level, ?string $targetPublicId): ?int
    {
        if ($level === AnnouncementRequestTargetLevel::COLLEGE || $targetPublicId === null) {
            return null;
        }

        return match ($level) {
            AnnouncementRequestTargetLevel::SCHOOL => School::where('public_id', $targetPublicId)->value('id'),
            AnnouncementRequestTargetLevel::PROGRAM => Program::where('public_id', $targetPublicId)->value('id'),
            default => null,
        };
    }

    /**
     * Upload physical file and insert File record into database
     */
    private function storeAttachment(UploadedFile $file): int
    {
        $path = $file->store('announcements', 'public');

        $fileRecord = File::create([
            'public_id' => (string) Str::uuid(),
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getClientMimeType() ?? $file->guessExtension() ?? 'application/octet-stream',
            'file_size' => (string) $file->getSize(),
            'file_path' => $path,
        ]);

        return $fileRecord->id;
    }
}
