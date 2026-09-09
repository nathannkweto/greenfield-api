<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Account
 * 
 * @property int $id
 * @property string $account_number
 * @property string $name
 * @property string $type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Transaction[] $transactions
 *
 * @package App\Models
 */
class Account extends Model
{
	protected $table = 'accounts';

	protected $fillable = [
		'account_number',
		'name',
		'type'
	];

	public function transactions()
	{
		return $this->hasMany(Transaction::class, 'debit_account_id');
	}
}
