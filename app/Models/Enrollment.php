<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Enrollment
 * 
 * @property int $id
 * @property string $public_id
 * @property int $student_id
 * @property int $curriculum_id
 * @property string $status
 * @property Carbon|null $completion_date
 * @property Carbon|null $drop_date
 * @property string|null $grade
 * @property float|null $points
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Curriculum $curriculum
 * @property Student $student
 *
 * @package App\Models
 */
class Enrollment extends Model
{
	protected $table = 'enrollments';

	protected $casts = [
		'student_id' => 'int',
		'curriculum_id' => 'int',
		'completion_date' => 'datetime',
		'drop_date' => 'datetime',
		'points' => 'float'
	];

	protected $fillable = [
		'public_id',
		'student_id',
		'curriculum_id',
		'status',
		'completion_date',
		'drop_date',
		'grade',
		'points'
	];

	public function curriculum()
	{
		return $this->belongsTo(Curriculum::class);
	}

	public function student()
	{
		return $this->belongsTo(Student::class);
	}
}
