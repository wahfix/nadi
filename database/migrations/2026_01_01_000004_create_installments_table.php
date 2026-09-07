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
        Schema::create('installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans')->cascadeOnDelete();
            $table->unsignedInteger('installment_number');
            $table->date('due_date')->index();
            $table->unsignedBigInteger('principal_due'); // Integer money Rupiah utuh
            $table->unsignedBigInteger('interest_due'); // Integer money Rupiah utuh
            $table->unsignedBigInteger('penalty_due')->default(0); // Integer money Rupiah utuh
            $table->unsignedBigInteger('total_due'); // principal_due + interest_due + penalty_due
            $table->unsignedBigInteger('principal_paid')->default(0);
            $table->unsignedBigInteger('interest_paid')->default(0);
            $table->unsignedBigInteger('penalty_paid')->default(0);
            $table->unsignedBigInteger('total_paid')->default(0);
            $table->unsignedBigInteger('remaining_amount'); // total_due - total_paid
            $table->string('status')->default('PENDING')->index(); // PENDING, PARTIALLY_PAID, PAID, OVERDUE, WAIVED
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installments');
    }
};
