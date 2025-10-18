<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_movies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('search_id')->constrained('searches')->onDelete('cascade');
            $table->foreignId('movie_id')->constrained('movies')->onDelete('restrict');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_movies');
    }
};
