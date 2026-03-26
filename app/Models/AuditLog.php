<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'request_id',
        'actor_id',
        'from_status',
        'to_status',
        'note',
        'created_at',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function request()
    {
        return $this->belongsTo(Request::class);
    }
}