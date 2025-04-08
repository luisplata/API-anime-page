<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('last_paginations', function (Blueprint $table) {
            $table->id();
            $table->string('type')->unique(); // Ejemplo: animes, peliculas, capitulos
            $table->integer('page');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('last_paginations');
    }
};
