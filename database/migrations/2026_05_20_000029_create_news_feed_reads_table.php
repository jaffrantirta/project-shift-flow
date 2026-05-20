<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_feed_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_feed_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->unique(['news_feed_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_feed_reads');
    }
};
