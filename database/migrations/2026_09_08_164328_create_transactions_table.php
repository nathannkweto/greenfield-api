<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('debit_account_id')->constrained('accounts');
            $table->foreignId('credit_account_id')->constrained('accounts');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'mobile_money', 'card']);
            $table->decimal('amount', 12, 2);
            $table->string('reference_number')->unique();
            $table->string('gateway_reference')->nullable();

            $table->nullableMorphs('entity');

            // Physical receipt tracking
            $table->string('manual_receipt_number')->nullable()->unique();
            $table->foreignId('receipt_book_id')->nullable()->constrained('receipt_books')->nullOnDelete();
            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
