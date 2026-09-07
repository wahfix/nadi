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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->string('loan_number')->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->unsignedBigInteger('principal_amount'); // Integer money Rupiah utuh
            $table->unsignedInteger('interest_rate'); // Basis points (e.g. 200 = 2.0%)
            $table->string('interest_method'); // FLAT, REDUCING_BALANCE
            $table->unsignedInteger('tenor'); // Number of installments
            $table->string('installment_frequency')->default('MONTHLY'); // MONTHLY, WEEKLY
            $table->date('disbursement_date')->nullable();
            $table->date('first_due_date');
            $table->date('maturity_date');
            $table->unsignedBigInteger('total_interest'); // Integer money Rupiah utuh
            $table->unsignedBigInteger('total_payable'); // principal + total_interest
            $table->unsignedBigInteger('installment_amount'); // per period
            $table->unsignedBigInteger('outstanding_principal');
            $table->unsignedBigInteger('outstanding_interest');
            $table->unsignedBigInteger('outstanding_penalty')->default(0);
            $table->unsignedBigInteger('outstanding_total');
            $table->string('status')->index(); // DRAFT, SUBMITTED, UNDER_REVIEW, APPROVED, REJECTED, READY_FOR_DISBURSEMENT, ACTIVE, OVERDUE, COMPLETED, DEFAULTED, CANCELLED
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('disbursed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('loan_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans')->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('changed_by')->constrained('users');
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_status_histories');
        Schema::dropIfExists('loans');
    }
};
