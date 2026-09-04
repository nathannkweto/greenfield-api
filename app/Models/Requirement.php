<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Requirement
 * 
 * @property int $id
 * @property string $public_id
 * @property int $program_id
 * @property string $description
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Program $program
 *
 * @package App\Models
 */
class Requirement extends Model
{
	protected $table = 'requirements';

	protected $casts = [
		'program_id' => 'int',
		'sort_order' => 'int'
	];

	protected $fillable = [
		'public_id',
		'program_id',
		'description',
		'sort_order'
	];

	public function program()
	{
		return $this->belongsTo(Program::class);
	}
}
