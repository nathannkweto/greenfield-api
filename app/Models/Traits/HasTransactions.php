<?php

namespace App\Models\Traits;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasTransactions
{
    /**
     * Get all transactions linked to this entity.
     */
    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'entity');
    }

    /**
     * Calculate current net ledger balance for a specific control account.
     * Default: Student Accounts Receivable (1200)
     */
    public function getBalance(string $accountNumber = '1200'): float
    {
        $account = Account::where('account_number', $accountNumber)->first();

        if (!$account) {
            return 0.00;
        }

        // Debits increase asset control balance (e.g., charges billed to student)
        $debits = $this->transactions()
            ->where('debit_account_id', $account->id)
            ->sum('amount');

        // Credits decrease asset control balance (e.g., payments made by student)
        $credits = $this->transactions()
            ->where('credit_account_id', $account->id)
            ->sum('amount');

        return (float) ($debits - $credits);
    }
}
