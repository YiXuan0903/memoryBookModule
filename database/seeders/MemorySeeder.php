<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Memory;
use App\Models\User;

class MemorySeeder extends Seeder
{
    public function run(): void
    {
        $alice = User::where('email', 'alice@example.com')->first();
        $bob = User::where('email', 'bob@example.com')->first();

        Memory::create([
            'user_id' => $alice->id,
            'title' => 'Graduation Day',
            'content' => 'I still remember the sunny afternoon when I graduated from college.',
            'created_at' => '2018-06-15',
        ]);

        Memory::create([
            'user_id' => $alice->id,
            'title' => 'First Job',
            'content' => 'My first job interview was so scary, but I nailed it!',
            'created_at' => '2019-01-10',
        ]);

        Memory::create([
            'user_id' => $bob->id,
            'title' => 'Trip to Japan',
            'content' => 'Walking under cherry blossoms in Kyoto was magical.',
            'created_at' => '2022-04-05',
        ]);
    }
}
