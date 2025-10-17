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
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->integer('search_id')->nullable();
            $table->string('title');
            $table->string('year')->nullable();
            $table->string('imdb_id');
            $table->string('type');
            $table->string('poster')->nullable();
            $table->timestamps();

            $table->foreign('search_id')->references('id')->on('searches')->onDelete('cascade');
            $table->index('imdb_id');
            $table->index('search_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};
