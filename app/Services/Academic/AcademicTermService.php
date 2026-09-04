<?php

namespace App\Services\Academic;

use App\Models\AcademicTerm;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AcademicTermService
{
    /**
     * List paginated academic terms.
     */
    public function listTerms(int $perPage = 15): LengthAwarePaginator
    {
        return AcademicTerm::query()
            ->orderBy('start_date', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get active/current academic term.
     */
    public function getCurrentTerm(): ?AcademicTerm
    {
        return AcademicTerm::where('is_current', true)->first();
    }

    /**
     * Get academic term by public UUID.
     */
    public function getByPublicId(string $publicId): AcademicTerm
    {
        return AcademicTerm::where('public_id', $publicId)
            ->with('course_offerings.course')
            ->firstOrFail();
    }

    /**
     * Create a new academic term.
     */
    public function createTerm(array $data): AcademicTerm
    {
        return DB::transaction(function () use ($data) {
            $isCurrent = $data['is_current'] ?? false;

            if ($isCurrent) {
                AcademicTerm::where('is_current', true)->update(['is_current' => false]);
            }

            return AcademicTerm::create([
                'public_id' => Str::uuid()->toString(),
                'year' => $data['year'],
                'term' => $data['term'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'is_current' => $isCurrent,
            ]);
        });
    }

    /**
     * Update academic term metadata or date ranges.
     */
    public function updateTerm(AcademicTerm $term, array $data): AcademicTerm
    {
        return DB::transaction(function () use ($term, $data) {
            if (!empty($data['is_current']) && $data['is_current'] === true) {
                AcademicTerm::where('id', '!=', $term->id)
                    ->where('is_current', true)
                    ->update(['is_current' => false]);
            }

            $term->update(array_filter($data, fn ($val) => $val !== null));

            return $term->fresh();
        });
    }

    /**
     * Mark a specific term as current and unset all others.
     */
    public function setCurrentTerm(AcademicTerm $term): AcademicTerm
    {
        return DB::transaction(function () use ($term) {
            AcademicTerm::where('is_current', true)->update(['is_current' => false]);
            $term->update(['is_current' => true]);

            return $term->fresh();
        });
    }

    /**
     * Delete an academic term.
     */
    public function deleteTerm(AcademicTerm $term): bool
    {
        return $term->delete();
    }
}
