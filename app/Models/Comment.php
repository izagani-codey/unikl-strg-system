<?php

namespace App\Models;

use App\Models\Request as GrantRequest;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'request_id',
        'user_id',
        'content',
        'is_internal',
        'created_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $comment) {
            if (empty($comment->created_at)) {
                $comment->created_at = now();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function request()
    {
        return $this->belongsTo(GrantRequest::class);
    }
}
