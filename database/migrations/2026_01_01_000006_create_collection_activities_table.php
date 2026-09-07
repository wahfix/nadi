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
        Schema::create('collection_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans');
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('collector_id')->constrained('users');
            $table->dateTime('contact_date');
            $table->string('contact_method'); // PHONE, WHATSAPP, IN_PERSON, OTHER
            $table->string('result'); // PAID, PROMISE_TO_PAY, NO_RESPONSE, CONTACT_FAILED, DISPUTED, OTHER
            $table->date('promise_to_pay_date')->nullable();
            $table->unsignedBigInteger('promise_to_pay_amount')->nullable(); // Integer money Rupiah utuh
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_activities');
    }
};
