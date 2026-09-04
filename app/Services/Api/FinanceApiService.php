<?php declare(strict_types=1);

namespace App\Services\Api;

use App\Models\Fee;
use App\Models\Student;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use OpenAPI\Server\Api\FinanceApiInterface;
use OpenAPI\Server\Model\FeeRequest;
use OpenAPI\Server\Model\FinanceTransactionPublicIdReversePostRequest;
use OpenAPI\Server\Model\NoContent200;
use OpenAPI\Server\Model\NoContent201;
use OpenAPI\Server\Model\TransactionRequest;

class FinanceApiService implements FinanceApiInterface
{
    /**
     * Create Fee/Invoice
     */
    public function financeFeeCreatePost(FeeRequest $FeeRequest): NoContent201
    {
        $student = Student::query()
            ->where('public_id', $FeeRequest->student_public_id)
            ->first();

        if (!$student) {
            throw new ModelNotFoundException('Student record not found.');
        }

        Fee::query()->create([
            'public_id' => Str::uuid()->toString(),
            'student_id' => $student->id,
            'title' => $FeeRequest->title,
            'type' => $FeeRequest->type,
            'amount' => $FeeRequest->amount,
            'currency' => $FeeRequest->currency,
            'due_date' => $FeeRequest->due_date,
        ]);

        return new NoContent201();
    }

    /**
     * Edit Fee/Invoice
     */
    public function financeFeePublicIdEditPost(string $public_id, FeeRequest $FeeRequest): NoContent200
    {
        $fee = Fee::query()
            ->where('public_id', $public_id)
            ->first();

        if (!$fee) {
            throw new ModelNotFoundException('Fee record not found.');
        }

        $student = Student::query()
            ->where('public_id', $FeeRequest->student_public_id)
            ->first();

        if (!$student) {
            throw new ModelNotFoundException('Student record not found.');
        }

        $fee->update([
            'student_id' => $student->id,
            'title' => $FeeRequest->title,
            'type' => $FeeRequest->type,
            'amount' => $FeeRequest->amount,
            'currency' => $FeeRequest->currency,
            'due_date' => $FeeRequest->due_date,
        ]);

        return new NoContent200();
    }

    /**
     * Record Payment Transaction
     */
    public function financeTransactionCreatePost(TransactionRequest $TransactionRequest): NoContent201
    {
        $student = Student::query()
            ->where('public_id', $TransactionRequest->student_public_id)
            ->first();

        if (!$student) {
            throw new ModelNotFoundException('Student record not found.');
        }

        $feeId = null;
        if ($TransactionRequest->invoice_public_id !== null) {
            $fee = Fee::query()
                ->where('public_id', $TransactionRequest->invoice_public_id)
                ->first();

            if (!$fee) {
                throw new ModelNotFoundException('Associated fee record not found.');
            }

            $feeId = $fee->id;
        }

        Transaction::query()->create([
            'public_id' => Str::uuid()->toString(),
            'student_id' => $student->id,
            'fee_id' => $feeId,
            'amount' => $TransactionRequest->amount,
            'method' => $TransactionRequest->method,
            'status' => 'completed',
        ]);

        return new NoContent201();
    }

    /**
     * Reverse Payment Transaction
     */
    public function financeTransactionPublicIdReversePost(
        string $public_id,
        ?FinanceTransactionPublicIdReversePostRequest $FinanceTransactionPublicIdReversePostRequest
    ): NoContent200 {
        $transaction = Transaction::query()
            ->where('public_id', $public_id)
            ->first();

        if (!$transaction) {
            throw new ModelNotFoundException('Transaction record not found.');
        }

        $transaction->update([
            'status' => 'reversed',
            'reversal_reason' => $FinanceTransactionPublicIdReversePostRequest?->reason,
        ]);

        return new NoContent200();
    }
}
