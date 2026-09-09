<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Models\Traits\HasFiles;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Announcement
 *
 * @property int $id
 * @property string $public_id
 * @property string $title
 * @property string $content
 * @property string $type
 * @property string $target_level
 * @property int|null $target_id
 * @property string $target_name
 * @property string $author
 * @property int|null $author_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property User|null $user
 *
 * @package App\Models
 */
class Announcement extends Model
{
	use HasFiles;
	protected $table = 'announcements';

	protected $casts = [
		'target_id' => 'int',
		'author_id' => 'int'
	];

	protected $fillable = [
		'public_id',
		'title',
		'content',
		'type',
		'target_level',
		'target_id',
		'target_name',
		'author',
		'author_id'
	];

	public function user()
	{
		return $this->belongsTo(User::class, 'author_id');
	}
}
