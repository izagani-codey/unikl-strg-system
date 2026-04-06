<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestType extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'default_template_id'];

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

    public function defaultTemplate()
    {
        return $this->belongsTo(FormTemplate::class, 'default_template_id');
    }
}