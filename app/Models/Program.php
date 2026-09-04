<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Program
 * 
 * @property int $id
 * @property string $public_id
 * @property int $school_id
 * @property string $code
 * @property string $title
 * @property string $level
 * @property int $duration_value
 * @property string $duration_unit
 * @property string|null $short_description
 * @property string|null $long_description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property School $school
 * @property Collection|Curriculum[] $curricula
 * @property Collection|Requirement[] $requirements
 * @property Collection|Student[] $students
 *
 * @package App\Models
 */
class Program extends Model
{
	protected $table = 'programs';

	protected $casts = [
		'school_id' => 'int',
		'duration_value' => 'int'
	];

	protected $fillable = [
		'public_id',
		'school_id',
		'code',
		'title',
		'level',
		'duration_value',
		'duration_unit',
		'short_description',
		'long_description'
	];

	public function school()
	{
		return $this->belongsTo(School::class);
	}

	public function curricula()
	{
		return $this->hasMany(Curriculum::class);
	}

	public function requirements()
	{
		return $this->hasMany(Requirement::class);
	}

	public function students()
	{
		return $this->hasMany(Student::class);
	}
}
