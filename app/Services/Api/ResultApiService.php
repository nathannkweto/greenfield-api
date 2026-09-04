<?php declare(strict_types=1);

namespace App\Services\Api;

use App\Models\Assessment;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use OpenAPI\Server\Api\ResultsApiInterface;
use OpenAPI\Server\Model\NoContent200;
use OpenAPI\Server\Model\ResultsPublicIdPublishPostRequest;

class ResultApiService implements ResultsApiInterface
{
    /**
     * Publish Assessment Results
     */
    public function resultsPublicIdPublishPost(
        string $public_id,
        ?ResultsPublicIdPublishPostRequest $ResultsPublicIdPublishPostRequest = null
    ): NoContent200 {
        $assessment = Assessment::query()
            ->where('public_id', $public_id)
            ->first();

        if (!$assessment) {
            throw new ModelNotFoundException('Assessment record not found.');
        }

        $notifyStudents = $ResultsPublicIdPublishPostRequest?->notify_students ?? true;

        $assessment->update([
            'is_published' => true,
            'published_at' => now(),
        ]);

        if ($notifyStudents) {
            // Dispatch notification event or job for enrolled students
        }

        return new NoContent200();
    }
}
