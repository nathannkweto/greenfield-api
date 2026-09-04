<?php

namespace App\Services\Delivery;

use App\Models\CourseMaterial;
use App\Models\CourseOffering;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CourseMaterialService
{
    /**
     * List course materials filtered by course offering or material type.
     */
    public function listMaterials(
        ?string $offeringPublicId = null,
        ?string $type = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return CourseMaterial::query()
            ->when($offeringPublicId, function ($query) use ($offeringPublicId) {
                $query->whereHas('course_offering', fn ($q) => $q->where('public_id', $offeringPublicId));
            })
            ->when($type, fn ($q) => $q->where('type', $type))
            ->with('course_offering.course')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get a single course material by public UUID.
     */
    public function getByPublicId(string $publicId): CourseMaterial
    {
        return CourseMaterial::where('public_id', $publicId)
            ->with('course_offering.course')
            ->firstOrFail();
    }

    /**
     * Create and attach a new material resource to a course offering.
     */
    public function createMaterial(array $data): CourseMaterial
    {
        return DB::transaction(function () use ($data) {
            $offering = CourseOffering::where('public_id', $data['course_offering_public_id'])->firstOrFail();

            return CourseMaterial::create([
                'public_id' => Str::uuid()->toString(),
                'course_offering_id' => $offering->id,
                'title' => $data['title'],
                'type' => $data['type'],
                'url' => $data['url'],
                'file_size' => $data['file_size'] ?? null,
            ])->load('course_offering.course');
        });
    }

    /**
     * Update material metadata or file references.
     */
    public function updateMaterial(CourseMaterial $material, array $data): CourseMaterial
    {
        return DB::transaction(function () use ($material, $data) {
            if (isset($data['course_offering_public_id'])) {
                $offering = CourseOffering::where('public_id', $data['course_offering_public_id'])->firstOrFail();
                $data['course_offering_id'] = $offering->id;
            }

            $material->update(array_filter([
                'course_offering_id' => $data['course_offering_id'] ?? $material->course_offering_id,
                'title' => $data['title'] ?? $material->title,
                'type' => $data['type'] ?? $material->type,
                'url' => $data['url'] ?? $material->url,
                'file_size' => $data['file_size'] ?? $material->file_size,
            ], fn ($val) => $val !== null));

            return $material->fresh('course_offering.course');
        });
    }

    /**
     * Remove a course material entry.
     */
    public function deleteMaterial(CourseMaterial $material): bool
    {
        return $material->delete();
    }
}
