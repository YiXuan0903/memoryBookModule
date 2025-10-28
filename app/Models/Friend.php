<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Friend extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'friend_id', 
        'category'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function friendUser()
    {
        return $this->belongsTo(User::class, 'friend_id');
    }

    public function getSharedMemoriesAttribute()
    {
        return Memory::whereHas('sharedUsers', function($query) {
            $query->where('users.id', $this->friend_id);
        })->where('user_id', $this->user_id)->get(['id', 'title']);
    }
}