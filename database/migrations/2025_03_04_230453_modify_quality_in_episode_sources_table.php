<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('episode_sources', function (Blueprint $table) {
            $table->string('quality')->default('HD')->change();
        });
    }

    public function down()
    {
        Schema::table('episode_sources', function (Blueprint $table) {
            $table->string('quality')->default(null)->change();
        });
    }
};
