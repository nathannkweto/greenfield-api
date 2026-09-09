<?php declare(strict_types=1);

namespace App\Models\Traits;

use App\Models\Fee;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasFees
{
    /**
     * Get all fees attached directly to this model (e.g. Program or School specific fees).
     */
    public function fees(): MorphMany
    {
        return $this->morphMany(Fee::class, 'feeable');
    }

    /**
     * Helper to create and attach a new fee to this model.
     *
     * @param array{title: string, amount_zmw: float, amount_usd?: float|null, frequency: string} $attributes
     */
    public function addFee(array $attributes): Fee
    {
        return $this->fees()->create($attributes);
    }
}
