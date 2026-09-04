<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Course
 * 
 * @property int $id
 * @property string $public_id
 * @property int $school_id
 * @property string $code
 * @property string $title
 * @property string|null $description
 * @property int $credits
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property School $school
 * @property Collection|CourseOffering[] $course_offerings
 * @property Collection|Curriculum[] $curricula
 *
 * @package App\Models
 */
class Course extends Model
{
	protected $table = 'courses';

	protected $casts = [
		'school_id' => 'int',
		'credits' => 'int'
	];

	protected $fillable = [
		'public_id',
		'school_id',
		'code',
		'title',
		'description',
		'credits'
	];

	public function school()
	{
		return $this->belongsTo(School::class);
	}

	public function course_offerings()
	{
		return $this->hasMany(CourseOffering::class);
	}

	public function curricula()
	{
		return $this->hasMany(Curriculum::class);
	}
}
