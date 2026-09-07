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
        Schema::create('identity_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('loan_id')->constrained('loans');
            $table->unsignedBigInteger('release_id')->nullable();
            $table->string('verification_method'); // GOVERNMENT_ID, ACCOUNT_MATCH, MANUAL_CHECK, OTHER
            $table->string('verified_name');
            $table->string('verified_id_number');
            $table->string('result'); // VERIFIED, FAILED, REQUIRES_REVIEW
            $table->foreignId('verifier_id')->constrained('users');
            $table->timestamp('verification_timestamp');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('collateral_releases', function (Blueprint $table) {
            $table->id();
            $table->string('release_number')->unique();
            $table->foreignId('collateral_id')->unique()->constrained('collaterals');
            $table->foreignId('loan_id')->constrained('loans');
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('verified_identity_id')->constrained('identity_verifications');
            $table->string('released_to_name');
            $table->string('relationship_to_customer');
            $table->date('release_date');
            $table->string('release_location');
            $table->foreignId('released_by')->constrained('users');
            $table->foreignId('witness_id')->nullable()->constrained('users');
            $table->string('customer_signature_reference')->nullable();
            $table->text('handover_notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collateral_releases');
        Schema::dropIfExists('identity_verifications');
    }
};
