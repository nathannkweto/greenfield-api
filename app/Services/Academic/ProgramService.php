<?php

namespace App\Services\Academic;

use App\Models\Program;
use App\Models\School;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProgramService
{
    /**
     * List programs optionally filtered by school.
     */
    public function listPrograms(?string $schoolPublicId = null, int $perPage = 15): LengthAwarePaginator
    {
        return Program::query()
            ->when($schoolPublicId, function ($query) use ($schoolPublicId) {
                $query->whereHas('school', fn ($q) => $q->where('public_id', $schoolPublicId));
            })
            ->with('school')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get a program by public UUID with its full curriculum structure.
     */
    public function getByPublicId(string $publicId): Program
    {
        return Program::where('public_id', $publicId)
            ->with(['school', 'curricula.course', 'students'])
            ->firstOrFail();
    }

    /**
     * Create a new academic program.
     */
    public function createProgram(array $data): Program
    {
        return DB::transaction(function () use ($data) {
            $school = School::where('public_id', $data['school_public_id'])->firstOrFail();

            return Program::create([
                'public_id' => Str::uuid()->toString(),
                'school_id' => $school->id,
                'code' => strtoupper($data['code']),
                'title' => $data['title'],
                'qualification' => $data['qualification'],
                'duration_months' => $data['duration_months'],
                'short_description' => $data['short_description'] ?? null,
                'long_description' => $data['long_description'] ?? null,
                'requirements' => $data['requirements'] ?? null,
            ]);
        });
    }

    /**
     * Update program details.
     */
    public function updateProgram(Program $program, array $data): Program
    {
        return DB::transaction(function () use ($program, $data) {
            if (isset($data['school_public_id'])) {
                $school = School::where('public_id', $data['school_public_id'])->firstOrFail();
                $data['school_id'] = $school->id;
            }

            if (isset($data['code'])) {
                $data['code'] = strtoupper($data['code']);
            }

            $program->update(array_filter($data, fn ($val) => $val !== null));

            return $program->fresh('school');
        });
    }

    /**
     * Delete an academic program.
     */
    public function deleteProgram(Program $program): bool
    {
        return DB::transaction(function () use ($program) {
            $program->curricula()->delete();
            return $program->delete();
        });
    }
}
