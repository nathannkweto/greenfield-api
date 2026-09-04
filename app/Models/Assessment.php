<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Assessment
 * 
 * @property int $id
 * @property string $public_id
 * @property int $curriculum_id
 * @property string $title
 * @property string|null $description
 * @property string $type
 * @property int|null $term
 * @property float $weight_percentage
 * @property float $max_score
 * @property Carbon|null $due_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Curriculum $curriculum
 * @property Collection|AssessmentResult[] $assessment_results
 *
 * @package App\Models
 */
class Assessment extends Model
{
	protected $table = 'assessments';

	protected $casts = [
		'curriculum_id' => 'int',
		'term' => 'int',
		'weight_percentage' => 'float',
		'max_score' => 'float',
		'due_date' => 'datetime'
	];

	protected $fillable = [
		'public_id',
		'curriculum_id',
		'title',
		'description',
		'type',
		'term',
		'weight_percentage',
		'max_score',
		'due_date'
	];

	public function curriculum()
	{
		return $this->belongsTo(Curriculum::class);
	}

	public function assessment_results()
	{
		return $this->hasMany(AssessmentResult::class);
	}
}
