<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Dean
 * 
 * @property int $id
 * @property string $public_id
 * @property int $user_id
 * @property int $school_id
 * @property int|null $lecturer_id
 * @property int|null $admin_id
 * @property bool $is_active
 * @property Carbon|null $appointed_at
 * @property Carbon|null $term_ends_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Admin|null $admin
 * @property Lecturer|null $lecturer
 * @property School $school
 * @property User $user
 *
 * @package App\Models
 */
class Dean extends Model
{
	protected $table = 'deans';

	protected $casts = [
		'user_id' => 'int',
		'school_id' => 'int',
		'lecturer_id' => 'int',
		'admin_id' => 'int',
		'is_active' => 'bool',
		'appointed_at' => 'datetime',
		'term_ends_at' => 'datetime'
	];

	protected $fillable = [
		'public_id',
		'user_id',
		'school_id',
		'lecturer_id',
		'admin_id',
		'is_active',
		'appointed_at',
		'term_ends_at'
	];

	public function admin()
	{
		return $this->belongsTo(Admin::class);
	}

	public function lecturer()
	{
		return $this->belongsTo(Lecturer::class);
	}

	public function school()
	{
		return $this->belongsTo(School::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
