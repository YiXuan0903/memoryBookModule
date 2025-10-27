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
    Schema::dropIfExists('memory_friend');
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('memory_friend', function (Blueprint $table) {
            //
        });
    }
};
