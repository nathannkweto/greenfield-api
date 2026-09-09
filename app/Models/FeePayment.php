<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class FeePayment
 * 
 * @property int $id
 * @property int $transaction_id
 * @property int $student_fee_id
 * @property float $amount
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property StudentFee $student_fee
 * @property Transaction $transaction
 *
 * @package App\Models
 */
class FeePayment extends Model
{
	protected $table = 'fee_payments';

	protected $casts = [
		'transaction_id' => 'int',
		'student_fee_id' => 'int',
		'amount' => 'float'
	];

	protected $fillable = [
		'transaction_id',
		'student_fee_id',
		'amount'
	];

	public function student_fee()
	{
		return $this->belongsTo(StudentFee::class);
	}

	public function transaction()
	{
		return $this->belongsTo(Transaction::class);
	}
}
