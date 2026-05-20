<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_feed_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_feed_id')->constrained()->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_url');
            $table->string('file_type', 60)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_feed_attachments');
    }
};
