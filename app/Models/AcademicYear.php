<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class AcademicYear
 * 
 * @property int $id
 * @property int $year
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|AcademicEvent[] $academic_events
 * @property Collection|AcademicTerm[] $academic_terms
 *
 * @package App\Models
 */
class AcademicYear extends Model
{
	protected $table = 'academic_years';

	protected $casts = [
		'year' => 'int',
		'start_date' => 'datetime',
		'end_date' => 'datetime'
	];

	protected $fillable = [
		'year',
		'start_date',
		'end_date'
	];

	public function academic_events()
	{
		return $this->hasMany(AcademicEvent::class);
	}

	public function academic_terms()
	{
		return $this->hasMany(AcademicTerm::class);
	}
}
