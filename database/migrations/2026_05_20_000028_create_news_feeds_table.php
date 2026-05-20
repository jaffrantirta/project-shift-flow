<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_feeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->string('type', 30)->default('announcement'); // announcement, news, alert
            $table->string('target_type', 30)->nullable(); // company, location, department
            $table->unsignedBigInteger('target_id')->nullable();
            $table->boolean('pinned')->default(false);
            $table->boolean('requires_confirmation')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_feeds');
    }
};
