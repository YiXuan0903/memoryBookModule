<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('mood')->nullable();
            $table->string('tags')->nullable();
            $table->string('template')->default('default');
            $table->boolean('is_public')->default(false);
            $table->string('sentiment')->default('neutral');
            $table->string('file_path')->nullable();
            $table->string('file_type')->nullable();
            $table->string('share_token')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memories');
    }
};
