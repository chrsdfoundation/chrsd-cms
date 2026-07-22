<?php

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix', 16)->nullable();
            $table->string('email')->unique();
            $table->string('mobile', 32)->nullable();

            // Personal
            $table->string('gender', 16)->default(Gender::Other->value);
            $table->date('date_of_birth')->nullable();
            $table->string('civil_status', 32)->nullable();
            $table->string('nationality', 64)->nullable();
            $table->text('address')->nullable();

            // Employment
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supervisor_id')->nullable()
                ->constrained('employees')->nullOnDelete();
            $table->string('employment_type', 32)->default(EmploymentType::Regular->value);
            $table->string('employee_status', 32)->default(EmployeeStatus::Active->value)->index();
            $table->date('hired_at')->nullable();
            $table->date('ended_at')->nullable();

            // Verification backbone (§Step 1)
            $table->verificationColumns();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['last_name', 'first_name']);
        });

        // Now that employees exists, wire departments.head_employee_id -> employees.id
        Schema::table('departments', function (Blueprint $table) {
            $table->foreign('head_employee_id')
                ->references('id')->on('employees')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['head_employee_id']);
        });
        Schema::dropIfExists('employees');
    }
};
