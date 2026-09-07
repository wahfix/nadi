<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number')->unique();
            $table->foreignId('loan_id')->constrained('loans');
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('installment_id')->nullable()->constrained('installments');
            $table->date('payment_date');
            $table->unsignedBigInteger('amount'); // Total payment received
            $table->unsignedBigInteger('principal_component'); // Allocated to principal
            $table->unsignedBigInteger('interest_component'); // Allocated to interest
            $table->unsignedBigInteger('penalty_component'); // Allocated to penalty
            $table->string('payment_method')->default('CASH'); // CASH, BANK_TRANSFER, QRIS, OTHER
            $table->string('reference_number')->nullable();
            $table->foreignId('received_by')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_reversals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->unique()->constrained('payments');
            $table->text('reason');
            $table->foreignId('reversed_by')->constrained('users');
            $table->timestamp('reversed_at')->useCurrent();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_reversals');
        Schema::dropIfExists('payments');
    }
};
