<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source', 20)->default('employee'); // admin, employee
            $table->string('type', 30)->default('general'); // general, bug, feature_request, complaint, suggestion
            $table->string('subject');
            $table->text('message');
            $table->unsignedTinyInteger('rating')->nullable(); // 1-5
            $table->string('status', 20)->default('pending'); // pending, in_review, resolved, dismissed
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
    }
};
