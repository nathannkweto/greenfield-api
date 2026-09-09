<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Models\Traits\HasFees;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class School
 *
 * @property int $id
 * @property string $public_id
 * @property string $name
 * @property string|null $description
 * @property int|null $dean_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property User|null $user
 * @property Collection|Course[] $courses
 * @property Collection|Dean[] $deans
 * @property Collection|Lecturer[] $lecturers
 * @property Collection|Program[] $programs
 *
 * @package App\Models
 */
class School extends Model
{
	use HasFees;
	protected $table = 'schools';

	protected $casts = [
		'dean_id' => 'int'
	];

	protected $fillable = [
		'public_id',
		'name',
		'description',
		'dean_id'
	];

	public function user()
	{
		return $this->belongsTo(User::class, 'dean_id');
	}

	public function courses()
	{
		return $this->hasMany(Course::class);
	}

	public function deans()
	{
		return $this->hasMany(Dean::class);
	}

	public function lecturers()
	{
		return $this->hasMany(Lecturer::class);
	}

	public function programs()
	{
		return $this->hasMany(Program::class);
	}
}
