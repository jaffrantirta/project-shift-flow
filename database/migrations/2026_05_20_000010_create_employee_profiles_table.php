<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('employee_code', 60)->nullable()->unique();
            $table->string('job_title')->nullable();
            $table->string('employment_type', 30)->nullable(); // full_time, part_time, casual, contractor
            $table->decimal('pay_rate', 10, 2)->nullable();
            $table->string('pay_type', 20)->nullable(); // hourly, salary
            $table->date('hire_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_profiles');
    }
};
