<?php declare(strict_types=1);

namespace App\Services\Api;

use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use OpenAPI\Server\Api\SchoolsApiInterface;
use OpenAPI\Server\Model\SchoolRequest;
use OpenAPI\Server\Model\StandardResponse;

/**
 * Custom DTO to satisfy StandardResponse's `object` typehint
 * while allowing Crell\Serde to serialize it cleanly.
 */
class SchoolResponseData
{
    public function __construct(
        public string $public_id,
        public string $name,
        public ?string $description = null,
        public ?string $dean_user_public_id = null,
    ) {}
}

class SchoolApiService implements SchoolsApiInterface
{
    /**
     * Create New School
     */
    public function schoolsCreatePost(SchoolRequest $SchoolRequest): StandardResponse
    {
        $deanId = $this->resolveDeanId($SchoolRequest->dean_user_public_id);

        $school = School::query()->create([
            'public_id' => Str::uuid()->toString(),
            'name' => $SchoolRequest->name,
            'description' => $SchoolRequest->description,
            'dean_id' => $deanId,
        ]);

        return new StandardResponse(
            status: 'success',
            message: 'School created successfully.',
            data: new SchoolResponseData(
                public_id: $school->public_id,
                name: $school->name,
                description: $school->description,
                dean_user_public_id: $SchoolRequest->dean_user_public_id,
            )
        );
    }

    /**
     * Edit School Details
     */
    public function schoolsPublicIdEditPost(string $public_id, SchoolRequest $SchoolRequest): StandardResponse
    {
        $school = School::query()
            ->where('public_id', $public_id)
            ->firstOrFail();

        $deanId = $this->resolveDeanId($SchoolRequest->dean_user_public_id);

        $school->update([
            'name' => $SchoolRequest->name,
            'description' => $SchoolRequest->description,
            'dean_id' => $deanId,
        ]);

        return new StandardResponse(
            status: 'success',
            message: 'School updated successfully.',
            data: new SchoolResponseData(
                public_id: $school->public_id,
                name: $school->name,
                description: $school->description,
                dean_user_public_id: $SchoolRequest->dean_user_public_id,
            )
        );
    }

    /**
     * Lookup user ID from public_id for dean assignment
     */
    private function resolveDeanId(?string $deanUserPublicId): ?int
    {
        if (blank($deanUserPublicId)) {
            return null;
        }

        $user = User::query()
            ->where('public_id', $deanUserPublicId)
            ->first();

        if (!$user) {
            throw new ModelNotFoundException('Dean user record not found.');
        }

        return $user->id;
    }
}
