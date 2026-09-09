<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class StudentFee
 * 
 * @property int $id
 * @property int $student_id
 * @property int $fee_id
 * @property float $amount_zmw
 * @property float|null $amount_usd
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Fee $fee
 * @property Student $student
 * @property Collection|FeePayment[] $fee_payments
 *
 * @package App\Models
 */
class StudentFee extends Model
{
	protected $table = 'student_fees';

	protected $casts = [
		'student_id' => 'int',
		'fee_id' => 'int',
		'amount_zmw' => 'float',
		'amount_usd' => 'float'
	];

	protected $fillable = [
		'student_id',
		'fee_id',
		'amount_zmw',
		'amount_usd'
	];

	public function fee()
	{
		return $this->belongsTo(Fee::class);
	}

	public function student()
	{
		return $this->belongsTo(Student::class);
	}

	public function fee_payments()
	{
		return $this->hasMany(FeePayment::class);
	}
}
