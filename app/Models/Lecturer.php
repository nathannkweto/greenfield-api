<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Lecturer
 * 
 * @property int $id
 * @property string $public_id
 * @property int $user_id
 * @property int $school_id
 * @property string $first_name
 * @property string|null $middle_name
 * @property string $last_name
 * @property Carbon|null $dob
 * @property string|null $address
 * @property string|null $emergency_contact
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property School $school
 * @property User $user
 * @property Collection|CourseOffering[] $course_offerings
 * @property Collection|Curriculum[] $curricula
 * @property Collection|Dean[] $deans
 *
 * @package App\Models
 */
class Lecturer extends Model
{
	protected $table = 'lecturers';

	protected $casts = [
		'user_id' => 'int',
		'school_id' => 'int',
		'dob' => 'datetime'
	];

	protected $fillable = [
		'public_id',
		'user_id',
		'school_id',
		'first_name',
		'middle_name',
		'last_name',
		'dob',
		'address',
		'emergency_contact'
	];

	public function school()
	{
		return $this->belongsTo(School::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function course_offerings()
	{
		return $this->hasMany(CourseOffering::class);
	}

	public function curricula()
	{
		return $this->hasMany(Curriculum::class);
	}

	public function deans()
	{
		return $this->hasMany(Dean::class);
	}
}
