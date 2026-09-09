<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class AssessmentResult
 * 
 * @property int $id
 * @property string $public_id
 * @property int $assessment_id
 * @property int $student_id
 * @property float|null $score
 * @property bool $is_published
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Assessment $assessment
 * @property Student $student
 *
 * @package App\Models
 */
class AssessmentResult extends Model
{
	protected $table = 'assessment_results';

	protected $casts = [
		'assessment_id' => 'int',
		'student_id' => 'int',
		'score' => 'float',
		'is_published' => 'bool'
	];

	protected $fillable = [
		'public_id',
		'assessment_id',
		'student_id',
		'score',
		'is_published'
	];

	public function assessment()
	{
		return $this->belongsTo(Assessment::class);
	}

	public function student()
	{
		return $this->belongsTo(Student::class);
	}
}
