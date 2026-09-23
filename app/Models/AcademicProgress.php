<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class AcademicProgress
 * 
 * @property int $id
 * @property int $student_id
 * @property int $current_year
 * @property int $credits_earned
 * @property float $cgpa
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Student $student
 *
 * @package App\Models
 */
class AcademicProgress extends Model
{
	protected $table = 'academic_progress';

	protected $casts = [
		'student_id' => 'int',
		'current_year' => 'int',
		'credits_earned' => 'int',
		'cgpa' => 'float'
	];

	protected $fillable = [
		'student_id',
		'current_year',
		'credits_earned',
		'cgpa'
	];

	public function student()
	{
		return $this->belongsTo(Student::class);
	}
}
