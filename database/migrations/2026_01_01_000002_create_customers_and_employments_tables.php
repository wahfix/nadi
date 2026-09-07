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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code')->unique();
            $table->string('full_name');
            $table->string('national_id_number')->index();
            $table->date('date_of_birth');
            $table->string('gender'); // MALE, FEMALE
            $table->string('phone')->index();
            $table->string('email')->nullable();
            $table->text('address');
            $table->string('city');
            $table->string('emergency_contact_name');
            $table->string('emergency_contact_phone');
            $table->string('status')->default('ACTIVE'); // ACTIVE, INACTIVE, BLOCKED
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('employments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('company_name');
            $table->string('department')->nullable();
            $table->string('position');
            $table->string('employment_type'); // PERMANENT, CONTRACT, SELF_EMPLOYED, OTHER
            $table->date('employment_start_date')->nullable();
            $table->unsignedBigInteger('estimated_monthly_income'); // Integer money Rupiah utuh
            $table->string('employment_status')->default('ACTIVE'); // ACTIVE, RESIGNED, TERMINATED, UNKNOWN
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employments');
        Schema::dropIfExists('customers');
    }
};
