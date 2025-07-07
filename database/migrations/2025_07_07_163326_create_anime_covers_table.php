<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('anime_covers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('anime_id');
            $table->string('cover_url');
            $table->string('path')->nullable();
            $table->string('filename')->nullable();
            $table->string('hash')->nullable();
            $table->timestamps();

            $table->foreign('anime_id')->references('id')->on('animes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anime_covers');
    }
};
