<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Admin
 * 
 * @property int $id
 * @property string $public_id
 * @property int $user_id
 * @property string $first_name
 * @property string|null $middle_name
 * @property string $last_name
 * @property string $employee_number
 * @property string $department
 * @property string $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property User $user
 * @property Collection|Dean[] $deans
 *
 * @package App\Models
 */
class Admin extends Model
{
	protected $table = 'admins';

	protected $casts = [
		'user_id' => 'int'
	];

	protected $fillable = [
		'public_id',
		'user_id',
		'first_name',
		'middle_name',
		'last_name',
		'employee_number',
		'department',
		'position'
	];

	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function deans()
	{
		return $this->hasMany(Dean::class);
	}
}
