<?php declare(strict_types=1);

namespace App\Services\Api;

use App\Jobs\AssignBulkFeeJob;
use App\Jobs\AssignDirectStudentsFeeJob;
use App\Jobs\AttachFeeToProgramJob;
use App\Models\Account;
use App\Models\Fee;
use App\Models\Program;
use App\Models\Student;
use App\Models\StudentFee;
use OpenAPI\Server\Api\FinanceApiInterface;
use OpenAPI\Server\Model\AttachFeeToProgramRequest;
use OpenAPI\Server\Model\BulkFeeAssignmentRequest;
use OpenAPI\Server\Model\DirectStudentFeeAssignmentRequest;
use OpenAPI\Server\Model\ErrorResponse;
use OpenAPI\Server\Model\FeeData;
use OpenAPI\Server\Model\FeeDataAccount;
use OpenAPI\Server\Model\FeeRequest;
use OpenAPI\Server\Model\FeeRequestFeeableType;
use OpenAPI\Server\Model\FeeResponse;
use OpenAPI\Server\Model\StandardResponse;
use OpenAPI\Server\Model\StudentFeeAssignmentRequest;
use OpenAPI\Server\Model\ValidationErrorResponse;

class FinanceApiService implements FinanceApiInterface
{
    /**
     * Create Master Fee Template
     */
    public function feesCreatePost(
        FeeRequest $FeeRequest
    ): FeeResponse | ErrorResponse | ValidationErrorResponse {
        /** @var Account|null $account */
        $account = Account::query()->find($FeeRequest->account_id);

        if (!$account) {
            return new ErrorResponse(
                message: 'Chart of Accounts GL account not found.',
                error: 'ACCOUNT_NOT_FOUND'
            );
        }

        $identifier = isset($FeeRequest->feeable_public_id)
            ? $FeeRequest->feeable_public_id
            : (isset($FeeRequest->program_public_id) ? $FeeRequest->program_public_id : null);

        $feeable = $this->resolveFeeable(
            isset($FeeRequest->feeable_type) ? $FeeRequest->feeable_type : null,
            $identifier
        );

        /** @var Fee $fee */
        $fee = Fee::query()->create([
            'title' => $FeeRequest->title,
            'amount_zmw' => $FeeRequest->amount_zmw,
            'amount_usd' => isset($FeeRequest->amount_usd) ? $FeeRequest->amount_usd : null,
            'frequency' => $FeeRequest->frequency->value,
            'account_id' => $account->getAttribute('id'),
            'feeable_type' => $feeable ? get_class($feeable) : null,
            'feeable_id' => $feeable?->getAttribute('id'),
        ]);

        return new FeeResponse(
            status: 'success',
            message: 'Fee template created successfully.',
            data: $this->transformFeeToData($fee, $account, $feeable)
        );
    }

    /**
     * Edit Fee Template
     */
    public function feesIdEditPost(
        int $id,
        FeeRequest $FeeRequest
    ): FeeResponse | ErrorResponse | ValidationErrorResponse {
        /** @var Fee|null $fee */
        $fee = Fee::query()->find($id);

        if (!$fee) {
            return new ErrorResponse(
                message: 'Fee template not found.',
                error: 'FEE_NOT_FOUND'
            );
        }

        /** @var Account|null $account */
        $account = Account::query()->find($FeeRequest->account_id);

        if (!$account) {
            return new ErrorResponse(
                message: 'Chart of Accounts GL account not found.',
                error: 'ACCOUNT_NOT_FOUND'
            );
        }

        $identifier = isset($FeeRequest->feeable_public_id)
            ? $FeeRequest->feeable_public_id
            : (isset($FeeRequest->program_public_id) ? $FeeRequest->program_public_id : null);

        $feeable = $this->resolveFeeable(
            isset($FeeRequest->feeable_type) ? $FeeRequest->feeable_type : null,
            $identifier
        );

        $fee->update([
            'title' => $FeeRequest->title,
            'amount_zmw' => $FeeRequest->amount_zmw,
            'amount_usd' => isset($FeeRequest->amount_usd) ? $FeeRequest->amount_usd : null,
            'frequency' => $FeeRequest->frequency->value,
            'account_id' => $account->getAttribute('id'),
            'feeable_type' => $feeable ? get_class($feeable) : null,
            'feeable_id' => $feeable?->getAttribute('id'),
        ]);

        return new FeeResponse(
            status: 'success',
            message: 'Fee template updated successfully.',
            data: $this->transformFeeToData($fee, $account, $feeable)
        );
    }

    /**
     * Assign Fee to Multiple Students Directly (Queued)
     */
    public function feesFeeIdAssignStudentsPost(
        int $fee_id,
        DirectStudentFeeAssignmentRequest $DirectStudentFeeAssignmentRequest
    ): StandardResponse | ErrorResponse | ValidationErrorResponse {
        /** @var Fee|null $fee */
        $fee = Fee::query()->find($fee_id);

        if (!$fee) {
            return new ErrorResponse(
                message: 'Fee template not found.',
                error: 'FEE_NOT_FOUND'
            );
        }

        // Queue direct assignment batch job
        AssignDirectStudentsFeeJob::dispatch(
            $fee,
            $DirectStudentFeeAssignmentRequest->student_public_ids,
            isset($DirectStudentFeeAssignmentRequest->due_date) ? $DirectStudentFeeAssignmentRequest->due_date : null,
            isset($DirectStudentFeeAssignmentRequest->override_amount_zmw) ? $DirectStudentFeeAssignmentRequest->override_amount_zmw : null
        );

        return new StandardResponse(
            status: 'success',
            message: 'Direct student fee assignment job queued successfully.',
            data: new class((int) $fee->getAttribute('id'), count($DirectStudentFeeAssignmentRequest->student_public_ids)) {
                public function __construct(
                    public int $fee_id,
                    public int $total_students
                ) {}
            }
        );
    }

    /**
     * Attach Fee to One or Multiple Programs (Queued)
     */
    public function feesFeeIdAttachProgramsPost(
        int $fee_id,
        AttachFeeToProgramRequest $AttachFeeToProgramRequest
    ): StandardResponse {
        /** @var Fee|null $fee */
        $fee = Fee::query()->find($fee_id);

        if (!$fee) {
            return new StandardResponse(
                status: 'error',
                message: 'Fee template not found.',
                data: new class {}
            );
        }

        $programPublicIds = isset($AttachFeeToProgramRequest->program_public_ids) && is_array($AttachFeeToProgramRequest->program_public_ids)
            ? $AttachFeeToProgramRequest->program_public_ids
            : [];

        // Queue program fee attachment and auto-billing job
        AttachFeeToProgramJob::dispatch($fee, $programPublicIds);

        return new StandardResponse(
            status: 'success',
            message: 'Fee program attachment job queued successfully.',
            data: new class((int) $fee->getAttribute('id'), count($programPublicIds)) {
                public function __construct(
                    public int $fee_id,
                    public int $program_count
                ) {}
            }
        );
    }

    /**
     * Attach a Universal Fee to a Program (Single Program)
     */
    public function feesIdAttachProgramPost(
        int $id,
        AttachFeeToProgramRequest $AttachFeeToProgramRequest
    ): FeeResponse | ErrorResponse | ValidationErrorResponse {
        /** @var Fee|null $fee */
        $fee = Fee::query()->find($id);

        if (!$fee) {
            return new ErrorResponse(
                message: 'Fee template not found.',
                error: 'FEE_NOT_FOUND'
            );
        }

        $programPublicIds = isset($AttachFeeToProgramRequest->program_public_ids) && !empty($AttachFeeToProgramRequest->program_public_ids)
            ? $AttachFeeToProgramRequest->program_public_ids
            : [];

        if (empty($programPublicIds) && isset($AttachFeeToProgramRequest->program_public_id)) {
            $programPublicIds = [$AttachFeeToProgramRequest->program_public_id];
        }

        /** @var Program|null $program */
        $program = Program::query()->whereIn('public_id', $programPublicIds)->first();

        if ($program) {
            $fee->update([
                'feeable_type' => Program::class,
                'feeable_id' => $program->getAttribute('id'),
            ]);
        }

        // Queue background sync for program students
        AttachFeeToProgramJob::dispatch($fee, $programPublicIds);

        /** @var Account $account */
        $account = Account::query()->findOrFail($fee->getAttribute('account_id'));

        return new FeeResponse(
            status: 'success',
            message: 'Fee attached to program successfully.',
            data: $this->transformFeeToData($fee, $account, $program)
        );
    }

    /**
     * Bulk Assign Fee to Filtered Students (Queued)
     */
    public function feesIdAssignBulkPost(
        int $id,
        BulkFeeAssignmentRequest $BulkFeeAssignmentRequest
    ): StandardResponse | ErrorResponse | ValidationErrorResponse {
        /** @var Fee|null $fee */
        $fee = Fee::query()->find($id);

        if (!$fee) {
            return new ErrorResponse(
                message: 'Fee template not found.',
                error: 'FEE_NOT_FOUND'
            );
        }

        AssignBulkFeeJob::dispatch($fee, [
            'student_numbers'   => isset($BulkFeeAssignmentRequest->student_numbers) ? $BulkFeeAssignmentRequest->student_numbers : null,
            'program_public_id' => isset($BulkFeeAssignmentRequest->program_public_id) ? $BulkFeeAssignmentRequest->program_public_id : null,
            'intake'            => isset($BulkFeeAssignmentRequest->intake) ? $BulkFeeAssignmentRequest->intake?->value : null,
            'registration_year' => isset($BulkFeeAssignmentRequest->registration_year) ? $BulkFeeAssignmentRequest->registration_year : null,
            'study_mode'        => isset($BulkFeeAssignmentRequest->study_mode) ? $BulkFeeAssignmentRequest->study_mode?->value : null,
            'status'            => isset($BulkFeeAssignmentRequest->status) ? $BulkFeeAssignmentRequest->status?->value : null,
        ]);

        return new StandardResponse(
            status: 'success',
            message: 'Bulk fee assignment job queued successfully.',
            data: new class((int) $fee->getAttribute('id')) {
                public function __construct(public int $fee_id) {}
            }
        );
    }

    /**
     * Assign Fee Directly to an Individual Student
     */
    public function studentsPublicIdFeesAssignPost(
        string $public_id,
        StudentFeeAssignmentRequest $StudentFeeAssignmentRequest
    ): StandardResponse | ErrorResponse | ValidationErrorResponse {
        /** @var Student|null $student */
        $student = Student::query()->where('public_id', $public_id)->first();

        if (!$student) {
            return new ErrorResponse(
                message: 'Student not found.',
                error: 'STUDENT_NOT_FOUND'
            );
        }

        /** @var Fee|null $fee */
        $fee = Fee::query()->find($StudentFeeAssignmentRequest->fee_id);

        if (!$fee) {
            return new ErrorResponse(
                message: 'Fee template not found.',
                error: 'FEE_NOT_FOUND'
            );
        }

        /** @var StudentFee $studentFee */
        $studentFee = StudentFee::query()->firstOrCreate(
            [
                'student_id' => $student->getAttribute('id'),
                'fee_id' => $fee->getAttribute('id'),
            ],
            [
                'amount_zmw' => isset($StudentFeeAssignmentRequest->override_amount_zmw) ? $StudentFeeAssignmentRequest->override_amount_zmw : $fee->getAttribute('amount_zmw'),
                'amount_usd' => isset($StudentFeeAssignmentRequest->override_amount_usd) ? $StudentFeeAssignmentRequest->override_amount_usd : $fee->getAttribute('amount_usd'),
            ]
        );

        return new StandardResponse(
            status: 'success',
            message: 'Fee assigned to student successfully.',
            data: new class((int) $studentFee->getAttribute('id')) {
                public function __construct(public int $student_fee_id) {}
            }
        );
    }

    /**
     * Resolve polymorphic scope model (Program or Student) using public_id or primary key id
     */
    private function resolveFeeable(?FeeRequestFeeableType $type, ?string $identifier): Program|Student|null
    {
        if (!$type || !$identifier) {
            return null;
        }

        return match ($type) {
            FeeRequestFeeableType::PROGRAM => Program::query()->where('public_id', $identifier)->first()
                ?? Program::query()->find($identifier),
            FeeRequestFeeableType::STUDENT => Student::query()->where('public_id', $identifier)->first()
                ?? Student::query()->find($identifier),
        };
    }

    /**
     * Transform Fee model to FeeData OpenAPI Serde DTO
     */
    private function transformFeeToData(Fee $fee, Account $account, Program|Student|null $feeable = null): FeeData
    {
        /** @var string|null $feeableType */
        $feeableType = $fee->getAttribute('feeable_type');

        return new FeeData(
            id: (int) $fee->getAttribute('id'),
            title: (string) $fee->getAttribute('title'),
            amount_zmw: (float) $fee->getAttribute('amount_zmw'),
            frequency: $fee->getAttribute('frequency'),
            account: new FeeDataAccount(
                id: (int) $account->getAttribute('id'),
                account_number: (string) $account->getAttribute('account_number'),
                name: (string) $account->getAttribute('name'),
                type: $account->getAttribute('type')
            ),
            amount_usd: $fee->getAttribute('amount_usd') !== null ? (float) $fee->getAttribute('amount_usd') : null,
            feeable_type: $feeableType ? class_basename($feeableType) : null,
            feeable_id: $feeable?->getAttribute('id')
        );
    }
}
