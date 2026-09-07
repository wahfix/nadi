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
        Schema::create('collaterals', function (Blueprint $table) {
            $table->id();
            $table->string('collateral_code')->unique();
            $table->foreignId('loan_id')->constrained('loans');
            $table->foreignId('customer_id')->constrained('customers');
            $table->string('collateral_type'); // DOCUMENT, VEHICLE, ELECTRONIC, OTHER
            $table->text('description');
            $table->string('identification_number');
            $table->unsignedBigInteger('estimated_value'); // Integer money Rupiah utuh
            $table->date('received_date');
            $table->text('condition_on_receipt');
            $table->string('storage_location');
            $table->string('custody_status')->default('PENDING')->index(); // PENDING, RECEIVED, IN_CUSTODY, READY_FOR_RELEASE, RELEASED, DISPUTED
            $table->foreignId('received_by')->constrained('users');
            $table->foreignId('released_by')->nullable()->constrained('users');
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collaterals');
    }
};
