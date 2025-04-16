<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('anonymous_client_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('anonymous_client_id'); // Relación con cliente anónimo
            $table->string('method', 10);        // GET, POST, etc.
            $table->string('path');              // /api/loquesea
            $table->string('full_url');
            $table->json('query_params')->nullable();
            $table->timestamps();

            $table->foreign('anonymous_client_id')
                ->references('id')
                ->on('anonymous_clients')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anonymous_client_logs');
    }
};
