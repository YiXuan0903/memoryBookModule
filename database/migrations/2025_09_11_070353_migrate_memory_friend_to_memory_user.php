<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Copy old data if the table still exists
        if (Schema::hasTable('memory_friend')) {
            $rows = DB::table('memory_friend')->get();

            foreach ($rows as $row) {
                // Get the actual user_id from the friends table
                $friend = DB::table('friends')->where('id', $row->friend_id)->first();

                if ($friend) {
                    DB::table('memory_user')->updateOrInsert(
                        [
                            'memory_id' => $row->memory_id,
                            'user_id'   => $friend->friend_id, // friend_id from friends table
                        ],
                        ['created_at' => now(), 'updated_at' => now()]
                    );
                }
            }
        }
    }

    public function down(): void
    {
        DB::table('memory_user')->truncate();
    }
};
