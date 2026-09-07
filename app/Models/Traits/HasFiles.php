<?php declare(strict_types=1);

namespace App\Models\Traits;

use App\Models\File;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasFiles
{
    /**
     * Get all attached files for the model.
     */
    public function files(): MorphToMany
    {
        return $this->morphToMany(File::class, 'fileable', 'fileables')
            ->withPivot(['collection', 'sort_order'])
            ->withTimestamps();
    }

    /**
     * Helper to attach a file with a collection.
     */
    public function attachFile(File|string|int $file, string $collection = 'attachment', int $sortOrder = 0): void
    {
        $fileId = match (true) {
            $file instanceof File => $file->id,
            is_numeric($file) => (int) $file,
            default => File::query()->where('public_id', $file)->value('id'),
        };

        if (!$fileId) {
            return;
        }

        $this->files()->attach($fileId, [
            'collection' => $collection,
            'sort_order' => $sortOrder,
        ]);
    }
}
