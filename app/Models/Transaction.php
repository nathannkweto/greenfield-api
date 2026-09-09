<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Transaction
 * 
 * @property int $id
 * @property int $debit_account_id
 * @property int $credit_account_id
 * @property string $payment_method
 * @property float $amount
 * @property string $reference_number
 * @property string|null $gateway_reference
 * @property string|null $entity_type
 * @property int|null $entity_id
 * @property string|null $manual_receipt_number
 * @property int|null $receipt_book_id
 * @property int|null $cashier_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property User|null $user
 * @property Account $account
 * @property ReceiptBook|null $receipt_book
 * @property Collection|FeePayment[] $fee_payments
 *
 * @package App\Models
 */
class Transaction extends Model
{
	protected $table = 'transactions';

	protected $casts = [
		'debit_account_id' => 'int',
		'credit_account_id' => 'int',
		'amount' => 'float',
		'entity_id' => 'int',
		'receipt_book_id' => 'int',
		'cashier_id' => 'int'
	];

	protected $fillable = [
		'debit_account_id',
		'credit_account_id',
		'payment_method',
		'amount',
		'reference_number',
		'gateway_reference',
		'entity_type',
		'entity_id',
		'manual_receipt_number',
		'receipt_book_id',
		'cashier_id'
	];

	public function user()
	{
		return $this->belongsTo(User::class, 'cashier_id');
	}

	public function account()
	{
		return $this->belongsTo(Account::class, 'debit_account_id');
	}

	public function receipt_book()
	{
		return $this->belongsTo(ReceiptBook::class);
	}

	public function fee_payments()
	{
		return $this->hasMany(FeePayment::class);
	}
}
