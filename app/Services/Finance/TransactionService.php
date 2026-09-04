<?php

namespace App\Services\Finance;

use App\Models\Invoice;
use App\Models\Student;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionService
{
    /**
     * List payment transactions with filters.
     */
    public function listTransactions(
        ?string $studentPublicId = null,
        ?string $invoicePublicId = null,
        ?string $status = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return Transaction::query()
            ->when($studentPublicId, function ($query) use ($studentPublicId) {
                $query->whereHas('student', fn ($q) => $q->where('public_id', $studentPublicId));
            })
            ->when($invoicePublicId, function ($query) use ($invoicePublicId) {
                $query->whereHas('invoice', fn ($q) => $q->where('public_id', $invoicePublicId));
            })
            ->when($status, fn ($q) => $q->where('status', $status))
            ->with(['student.user', 'invoice'])
            ->latest('transaction_date')
            ->paginate($perPage);
    }

    /**
     * Get transaction by public UUID.
     */
    public function getByPublicId(string $publicId): Transaction
    {
        return Transaction::where('public_id', $publicId)
            ->with(['student.user', 'invoice'])
            ->firstOrFail();
    }

    /**
     * Get transaction by reference code.
     */
    public function getByReferenceCode(string $referenceCode): Transaction
    {
        return Transaction::where('reference_code', $referenceCode)
            ->with(['student.user', 'invoice'])
            ->firstOrFail();
    }

    /**
     * Process and log a payment transaction, updating invoice status automatically.
     */
    public function recordTransaction(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $student = Student::where('public_id', $data['student_public_id'])->firstOrFail();

            $invoice = null;
            if (!empty($data['invoice_public_id'])) {
                $invoice = Invoice::where('public_id', $data['invoice_public_id'])->firstOrFail();
            }

            $transaction = Transaction::create([
                'public_id' => Str::uuid()->toString(),
                'student_id' => $student->id,
                'invoice_id' => $invoice?->id,
                'amount' => (float) $data['amount'],
                'currency' => strtoupper($data['currency'] ?? 'USD'),
                'method' => strtoupper($data['method']),
                'status' => strtoupper($data['status'] ?? 'PENDING'),
                'reference_code' => $data['reference_code'] ?? 'TXN-' . strtoupper(Str::random(10)),
                'transaction_date' => $data['transaction_date'] ?? Carbon::now(),
            ]);

            if ($invoice) {
                $this->syncInvoicePaymentStatus($invoice);
            }

            return $transaction->load(['student.user', 'invoice']);
        });
    }

    /**
     * Update transaction status and adjust linked invoice state.
     */
    public function updateTransactionStatus(Transaction $transaction, string $status): Transaction
    {
        return DB::transaction(function () use ($transaction, $status) {
            $transaction->update(['status' => strtoupper($status)]);

            if ($transaction->invoice) {
                $this->syncInvoicePaymentStatus($transaction->invoice);
            }

            return $transaction->fresh(['student.user', 'invoice']);
        });
    }

    /**
     * Recalculate total successful payments against an invoice and update its status.
     */
    public function syncInvoicePaymentStatus(Invoice $invoice): void
    {
        $successfulPaymentsTotal = Transaction::where('invoice_id', $invoice->id)
            ->where('status', 'SUCCESSFUL')
            ->sum('amount');

        if ($successfulPaymentsTotal >= $invoice->amount) {
            $invoice->update(['status' => 'PAID']);
        } elseif ($successfulPaymentsTotal > 0) {
            $invoice->update(['status' => 'PARTIALLY_PAID']);
        } else {
            $invoice->update(['status' => 'UNPAID']);
        }
    }

    /**
     * Delete transaction record.
     */
    public function deleteTransaction(Transaction $transaction): bool
    {
        return DB::transaction(function () use ($transaction) {
            $invoice = $transaction->invoice;
            $deleted = $transaction->delete();

            if ($invoice) {
                $this->syncInvoicePaymentStatus($invoice);
            }

            return $deleted;
        });
    }
}
