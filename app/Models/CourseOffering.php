<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class CourseOffering
 * 
 * @property int $id
 * @property string $public_id
 * @property int $course_id
 * @property int|null $lecturer_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Course $course
 * @property Lecturer|null $lecturer
 *
 * @package App\Models
 */
class CourseOffering extends Model
{
	protected $table = 'course_offerings';

	protected $casts = [
		'course_id' => 'int',
		'lecturer_id' => 'int'
	];

	protected $fillable = [
		'public_id',
		'course_id',
		'lecturer_id'
	];

	public function course()
	{
		return $this->belongsTo(Course::class);
	}

	public function lecturer()
	{
		return $this->belongsTo(Lecturer::class);
	}
}
