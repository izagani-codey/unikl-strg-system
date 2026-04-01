<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestType extends Model
{
    protected $fillable = ['name', 'slug', 'description'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function requests()
    {
        return $this->hasMany(Request::class);
    }

    public function requestsCount()
    {
        return $this->requests()->count();
    }
}