<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('cap_reports', function ($table) {
        $table->boolean('resolved')->default(false);
        $table->timestamp('resolved_at')->nullable();
    });
}

public function down()
{
    Schema::table('cap_reports', function ($table) {
        $table->dropColumn('resolved');
        $table->dropColumn('resolved_at');
    });
}
};
