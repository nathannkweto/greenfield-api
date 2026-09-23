<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class Fee
 *
 * @property int $id
 * @property string $title
 * @property float $amount_zmw
 * @property float|null $amount_usd
 * @property string $frequency
 * @property string|null $feeable_type
 * @property int|null $feeable_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Collection|Student[] $students
 *
 * @package App\Models
 */
class Fee extends Model
{
	protected $table = 'fees';

	protected $casts = [
		'amount_zmw' => 'float',
		'amount_usd' => 'float',
		'feeable_id' => 'int'
	];

	protected $fillable = [
		'title',
		'amount_zmw',
		'amount_usd',
		'frequency',
		'feeable_type',
		'feeable_id'
	];

	public function students()
	{
		return $this->belongsToMany(Student::class, 'student_fees')
					->withPivot('id', 'amount_zmw', 'amount_usd')
					->withTimestamps();
	}

    public function feeable(): MorphTo
    {
        return $this->morphTo();
    }
}
