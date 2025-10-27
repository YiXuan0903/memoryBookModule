<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memory_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('memory_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('memory_id')->references('id')->on('memories')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->unique(['memory_id', 'user_id']); // prevent duplicates
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memory_user');
    }
};
