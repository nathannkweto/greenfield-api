<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Models\Traits\HasFees;
use App\Models\Traits\HasFiles;
use App\Models\Traits\HasTransactions;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Student
 *
 * @property int $id
 * @property string $public_id
 * @property int $user_id
 * @property int $program_id
 * @property string $application_number
 * @property string|null $admission_number
 * @property string|null $student_number
 * @property string $first_name
 * @property string|null $middle_names
 * @property string $last_name
 * @property string $email
 * @property string $phone
 * @property Carbon|null $dob
 * @property string|null $address
 * @property string|null $emergency_contact
 * @property string $sex
 * @property string $marital_status
 * @property string $nationality
 * @property string|null $nrc_number
 * @property string|null $passport_number
 * @property string $intake
 * @property string $study_mode
 * @property string $status
 * @property Carbon $application_date
 * @property Carbon|null $admission_date
 * @property Carbon|null $rejection_date
 * @property Carbon|null $graduation_date
 * @property float $cgpa
 * @property int $credits_completed
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Program $program
 * @property User $user
 * @property Collection|AssessmentResult[] $assessment_results
 * @property Collection|Enrollment[] $enrollments
 * @property Collection|Fee[] $fees
 *
 * @package App\Models
 */
class Student extends Model
{
	use HasFiles;
	use HasFees;
    use HasTransactions;
	protected $table = 'students';

	protected $casts = [
		'user_id' => 'int',
		'program_id' => 'int',
		'dob' => 'datetime',
		'application_date' => 'datetime',
		'admission_date' => 'datetime',
		'rejection_date' => 'datetime',
		'graduation_date' => 'datetime',
		'cgpa' => 'float',
		'credits_completed' => 'int'
	];

	protected $fillable = [
		'public_id',
		'user_id',
		'program_id',
		'application_number',
		'admission_number',
		'student_number',
		'first_name',
		'middle_names',
		'last_name',
		'email',
		'phone',
		'dob',
		'address',
		'emergency_contact',
		'sex',
		'marital_status',
		'nationality',
		'nrc_number',
		'passport_number',
		'intake',
		'study_mode',
		'status',
		'application_date',
		'admission_date',
		'rejection_date',
		'graduation_date',
		'cgpa',
		'credits_completed'
	];

	public function program()
	{
		return $this->belongsTo(Program::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function assessment_results()
	{
		return $this->hasMany(AssessmentResult::class);
	}

	public function enrollments()
	{
		return $this->hasMany(Enrollment::class);
	}

	public function fees()
	{
		return $this->belongsToMany(Fee::class, 'student_fees')
					->withPivot('id', 'amount_zmw', 'amount_usd')
					->withTimestamps();
	}
    public function studentFees()
    {
        return $this->hasMany(StudentFee::class);
    }
}
