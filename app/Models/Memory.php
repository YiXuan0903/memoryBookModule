<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Memory extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'content',
        'tags',
        'visibility',
        'mood',
        'user_id',
        'file_path',
        'file_type',        
        'share_token',
        'is_public',      
        'sentiment',
        'template',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    protected $dates = ['deleted_at'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function sharedUsers(){
        return $this->belongsToMany(User::class, 'memory_user', 'memory_id', 'user_id')
                    ->withTimestamps();
    }

    public function scopeVisible($query, $visibility = 'all') {
        if ($visibility === 'public') {
            return $query->where('is_public', true);
        } elseif ($visibility === 'private') {
            return $query->where('is_public', false);
        }
        return $query;
    }

     public function getFileUrlAttribute()
    {
        return $this->file_path ? asset('storage/' . $this->file_path) : null;
    }

    public function isSharedPublicly()
    {
        return !empty($this->share_token);
    }

    public function getShareUrlAttribute()
    {
        if (!$this->share_token) {
            return null;
        }
        
        return url('/memories/' . $this->id . '?shared_token=' . $this->share_token);
    }
}