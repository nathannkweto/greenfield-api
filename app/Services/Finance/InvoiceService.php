<?php

namespace App\Services\Finance;

use App\Models\Invoice;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceService
{
    /**
     * List invoices with optional filters for student, billing status, or invoice type.
     */
    public function listInvoices(
        ?string $studentPublicId = null,
        ?string $status = null,
        ?string $type = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return Invoice::query()
            ->when($studentPublicId, function ($query) use ($studentPublicId) {
                $query->whereHas('student', fn ($q) => $q->where('public_id', $studentPublicId));
            })
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($type, fn ($q) => $q->where('type', $type))
            ->with(['student.user', 'transactions'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get single invoice record with line payments.
     */
    public function getByPublicId(string $publicId): Invoice
    {
        return Invoice::where('public_id', $publicId)
            ->with(['student.user', 'student.program', 'transactions'])
            ->firstOrFail();
    }

    /**
     * Issue a new invoice to a student.
     */
    public function createInvoice(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            $student = Student::where('public_id', $data['student_public_id'])->firstOrFail();

            return Invoice::create([
                'public_id' => Str::uuid()->toString(),
                'student_id' => $student->id,
                'title' => $data['title'],
                'type' => $data['type'],
                'amount' => (float) $data['amount'],
                'currency' => strtoupper($data['currency'] ?? 'USD'),
                'due_date' => $data['due_date'],
                'status' => $data['status'] ?? 'UNPAID',
            ])->load('student.user');
        });
    }

    /**
     * Update existing invoice details.
     */
    public function updateInvoice(Invoice $invoice, array $data): Invoice
    {
        return DB::transaction(function () use ($invoice, $data) {
            $invoice->update(array_filter([
                'title' => $data['title'] ?? $invoice->title,
                'type' => $data['type'] ?? $invoice->type,
                'amount' => isset($data['amount']) ? (float) $data['amount'] : $invoice->amount,
                'currency' => isset($data['currency']) ? strtoupper($data['currency']) : $invoice->currency,
                'due_date' => $data['due_date'] ?? $invoice->due_date,
                'status' => $data['status'] ?? $invoice->status,
            ], fn ($val) => $val !== null));

            return $invoice->fresh('student.user');
        });
    }

    /**
     * Cancel or void an open invoice.
     */
    public function cancelInvoice(Invoice $invoice): Invoice
    {
        $invoice->update(['status' => 'CANCELLED']);

        return $invoice->fresh();
    }

    /**
     * Calculate outstanding account balances for a student profile.
     */
    public function calculateStudentBalance(string $studentPublicId): array
    {
        $student = Student::where('public_id', $studentPublicId)->firstOrFail();

        $invoices = Invoice::where('student_id', $student->id)
            ->where('status', '!=', 'CANCELLED')
            ->get();

        $totalBilled = $invoices->sum('amount');

        $totalPaid = DB::table('transactions')
            ->where('student_id', $student->id)
            ->where('status', 'SUCCESSFUL')
            ->sum('amount');

        return [
            'student_id' => $student->public_id,
            'total_billed' => (float) $totalBilled,
            'total_paid' => (float) $totalPaid,
            'outstanding_balance' => (float) max(0, $totalBilled - $totalPaid),
        ];
    }

    /**
     * Delete an invoice and related transactions.
     */
    public function deleteInvoice(Invoice $invoice): bool
    {
        return DB::transaction(function () use ($invoice) {
            $invoice->transactions()->delete();
            return $invoice->delete();
        });
    }
}
