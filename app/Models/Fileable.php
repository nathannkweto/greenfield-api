<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Fileable
 * 
 * @property int $file_id
 * @property string $fileable_type
 * @property string $fileable_id
 * @property string $collection
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property File $file
 *
 * @package App\Models
 */
class Fileable extends Model
{
	protected $table = 'fileables';
	public $incrementing = false;

	protected $casts = [
		'file_id' => 'int',
		'sort_order' => 'int'
	];

	protected $fillable = [
		'sort_order'
	];

	public function file()
	{
		return $this->belongsTo(File::class);
	}
}
