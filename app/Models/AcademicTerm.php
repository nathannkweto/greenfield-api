<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class AcademicTerm
 *
 * @property int $id
 * @property int $academic_year_id
 * @property string $term
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property AcademicYear $academic_year
 *
 * @package App\Models
 */
class AcademicTerm extends Model
{
	protected $table = 'academic_terms';

	protected $casts = [
		'academic_year_id' => 'int',
		'start_date' => 'datetime',
		'end_date' => 'datetime'
	];

	protected $fillable = [
		'academic_year_id',
		'term',
		'start_date',
		'end_date'
	];

	public function academicYear()
	{
		return $this->belongsTo(AcademicYear::class);
	}
}
