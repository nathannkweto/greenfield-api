<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ReceiptBook
 * 
 * @property int $id
 * @property string $book_number
 * @property int $start_number
 * @property int $end_number
 * @property int|null $current_number
 * @property int $assigned_to_user_id
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property User $user
 * @property Collection|Transaction[] $transactions
 *
 * @package App\Models
 */
class ReceiptBook extends Model
{
	protected $table = 'receipt_books';

	protected $casts = [
		'start_number' => 'int',
		'end_number' => 'int',
		'current_number' => 'int',
		'assigned_to_user_id' => 'int'
	];

	protected $fillable = [
		'book_number',
		'start_number',
		'end_number',
		'current_number',
		'assigned_to_user_id',
		'status'
	];

	public function user()
	{
		return $this->belongsTo(User::class, 'assigned_to_user_id');
	}

	public function transactions()
	{
		return $this->hasMany(Transaction::class);
	}
}
