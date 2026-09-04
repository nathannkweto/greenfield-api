<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Applicant
 * 
 * @property int $id
 * @property string $public_id
 * @property int|null $user_id
 * @property string $first_name
 * @property string|null $middle_names
 * @property string $last_name
 * @property string $email
 * @property string $phone
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property User|null $user
 *
 * @package App\Models
 */
class Applicant extends Model
{
	protected $table = 'applicants';

	protected $casts = [
		'user_id' => 'int'
	];

	protected $fillable = [
		'public_id',
		'user_id',
		'first_name',
		'middle_names',
		'last_name',
		'email',
		'phone'
	];

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
