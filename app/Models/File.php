<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class File
 * 
 * @property string $id
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string $mime_type
 * @property int $size
 * @property int|null $uploaded_by_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property User|null $user
 * @property Collection|Fileable[] $fileables
 *
 * @package App\Models
 */
class File extends Model
{
	protected $table = 'files';
	public $incrementing = false;

	protected $casts = [
		'size' => 'int',
		'uploaded_by_id' => 'int'
	];

	protected $fillable = [
		'disk',
		'path',
		'original_name',
		'mime_type',
		'size',
		'uploaded_by_id'
	];

	public function user()
	{
		return $this->belongsTo(User::class, 'uploaded_by_id');
	}

	public function fileables()
	{
		return $this->hasMany(Fileable::class);
	}
}
