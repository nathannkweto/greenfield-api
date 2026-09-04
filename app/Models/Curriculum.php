<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Curriculum
 * 
 * @property int $id
 * @property string $public_id
 * @property int $program_id
 * @property int $course_id
 * @property int|null $lecturer_id
 * @property int $year
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Course $course
 * @property Lecturer|null $lecturer
 * @property Program $program
 * @property Collection|Assessment[] $assessments
 * @property Collection|Enrollment[] $enrollments
 *
 * @package App\Models
 */
class Curriculum extends Model
{
	protected $table = 'curriculums';

	protected $casts = [
		'program_id' => 'int',
		'course_id' => 'int',
		'lecturer_id' => 'int',
		'year' => 'int'
	];

	protected $fillable = [
		'public_id',
		'program_id',
		'course_id',
		'lecturer_id',
		'year'
	];

	public function course()
	{
		return $this->belongsTo(Course::class);
	}

	public function lecturer()
	{
		return $this->belongsTo(Lecturer::class);
	}

	public function program()
	{
		return $this->belongsTo(Program::class);
	}

	public function assessments()
	{
		return $this->hasMany(Assessment::class);
	}

	public function enrollments()
	{
		return $this->hasMany(Enrollment::class);
	}
}
