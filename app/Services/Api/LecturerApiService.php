<?php declare(strict_types=1);

namespace App\Services\Api;

use App\Models\CourseOffering;
use App\Models\Lecturer;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use OpenAPI\Server\Api\LecturersApiInterface;
use OpenAPI\Server\Model\LecturerRequest;
use OpenAPI\Server\Model\LecturersPublicIdAssignPostRequest;
use OpenAPI\Server\Model\NoContent200;
use OpenAPI\Server\Model\NoContent201;

class LecturerApiService implements LecturersApiInterface
{
    /**
     * Create Lecturer Profile & User Account
     */
    public function lecturersCreatePost(LecturerRequest $LecturerRequest): NoContent201
    {
        $school = School::query()
            ->where('public_id', $LecturerRequest->school_public_id)
            ->first();

        if (!$school) {
            throw new ModelNotFoundException('School record not found.');
        }

        DB::transaction(function () use ($school, $LecturerRequest) {
            $randomPassword = Str::random(8);

            $user = User::query()->create([
                'public_id' => Str::uuid()->toString(),
                'email' => $LecturerRequest->email,
                'phone' => $LecturerRequest->phone,
                'password' => Hash::make($randomPassword),
            ]);

            Lecturer::query()->create([
                'public_id' => Str::uuid()->toString(),
                'user_id' => $user->id,
                'school_id' => $school->id,
                'first_name' => $LecturerRequest->first_name,
                'middle_name' => $LecturerRequest->middle_name,
                'last_name' => $LecturerRequest->last_name,
                'dob' => $LecturerRequest->dob,
                'address' => $LecturerRequest->address,
                'emergency_contact' => $LecturerRequest->emergency_contact,
            ]);
        });

        return new NoContent201();
    }

    /**
     * Edit Lecturer Profile & User Information
     */
    public function lecturersPublicIdEditPost(string $public_id, LecturerRequest $LecturerRequest): NoContent200
    {
        $lecturer = Lecturer::query()
            ->where('public_id', $public_id)
            ->first();

        if (!$lecturer) {
            throw new ModelNotFoundException('Lecturer record not found.');
        }

        $school = School::query()
            ->where('public_id', $LecturerRequest->school_public_id)
            ->first();

        if (!$school) {
            throw new ModelNotFoundException('School record not found.');
        }

        DB::transaction(function () use ($lecturer, $school, $LecturerRequest) {
            if ($lecturer->user) {
                $lecturer->user->update([
                    'email' => $LecturerRequest->email,
                    'phone' => $LecturerRequest->phone,
                ]);
            }

            $lecturer->update([
                'school_id' => $school->id,
                'first_name' => $LecturerRequest->first_name,
                'middle_name' => $LecturerRequest->middle_name,
                'last_name' => $LecturerRequest->last_name,
                'dob' => $LecturerRequest->dob,
                'address' => $LecturerRequest->address,
                'emergency_contact' => $LecturerRequest->emergency_contact,
            ]);
        });

        return new NoContent200();
    }

    /**
     * Assign Courses to Lecturer
     */
    public function lecturersPublicIdAssignPost(
        string $public_id,
        LecturersPublicIdAssignPostRequest $LecturersPublicIdAssignPostRequest
    ): NoContent200 {
        $lecturer = Lecturer::query()
            ->where('public_id', $public_id)
            ->first();

        if (!$lecturer) {
            throw new ModelNotFoundException('Lecturer record not found.');
        }

        DB::transaction(function () use ($lecturer, $LecturersPublicIdAssignPostRequest) {
            CourseOffering::query()
                ->whereIn('public_id', $LecturersPublicIdAssignPostRequest->course_offering_public_ids)
                ->update([
                    'lecturer_id' => $lecturer->id,
                ]);
        });

        return new NoContent200();
    }
}
