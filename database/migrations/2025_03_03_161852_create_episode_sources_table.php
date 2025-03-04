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
        Schema::create('episode_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('episode_id')->constrained()->onDelete('cascade');
            $table->string('quality'); // e.g., 720p, 1080p
            $table->string('url');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('episode_sources');
    }
};
