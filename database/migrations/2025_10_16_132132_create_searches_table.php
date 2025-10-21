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
        Schema::create('searches', function (Blueprint $table) {
            $table->id();
            $table->uuid('guest_uuid');
            $table->string('query');
            $table->integer('total_results')->default(0);
            $table->integer('total_pages')->default(0);
            $table->boolean('no_results')->default(false);
            $table->integer('pages_loaded')->default(0);
            $table->timestamps();

            $table->foreign('guest_uuid')->references('uuid')->on('guests')->onDelete('cascade');
            $table->index('guest_uuid');
            $table->index('query');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('searches');
    }
};
