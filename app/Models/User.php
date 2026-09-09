<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Models\Traits\HasPortalRoles;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Class User
 *
 * @property int $id
 * @property string $public_id
 * @property string $email
 * @property string|null $phone
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Collection|Admin[] $admins
 * @property Collection|Announcement[] $announcements
 * @property Collection|Applicant[] $applicants
 * @property Collection|Dean[] $deans
 * @property Collection|File[] $files
 * @property Collection|Lecturer[] $lecturers
 * @property Collection|ReceiptBook[] $receipt_books
 * @property Collection|School[] $schools
 * @property Collection|Student[] $students
 * @property Collection|Transaction[] $transactions
 *
 * @package App\Models
 */
class User extends Authenticatable
{
	use HasPortalRoles;
    use Notifiable;
	protected $table = 'users';

	protected $hidden = [
		'password',
		'remember_token'
	];

	protected $fillable = [
		'public_id',
		'email',
		'phone',
		'password',
		'remember_token'
	];

	public function admins()
	{
		return $this->hasMany(Admin::class);
	}

	public function announcements()
	{
		return $this->hasMany(Announcement::class, 'author_id');
	}

	public function applicants()
	{
		return $this->hasMany(Applicant::class);
	}

	public function deans()
	{
		return $this->hasMany(Dean::class);
	}

	public function files()
	{
		return $this->hasMany(File::class, 'uploaded_by_id');
	}

	public function lecturers()
	{
		return $this->hasMany(Lecturer::class);
	}

	public function receipt_books()
	{
		return $this->hasMany(ReceiptBook::class, 'assigned_to_user_id');
	}

	public function schools()
	{
		return $this->hasMany(School::class, 'dean_id');
	}

	public function students()
	{
		return $this->hasMany(Student::class);
	}

	public function transactions()
	{
		return $this->hasMany(Transaction::class, 'cashier_id');
	}
}
