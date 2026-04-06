<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestType extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'default_template_id', 'field_schema', 'requires_vot', 'metadata', 'is_active'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'field_schema' => 'array',
        'metadata' => 'array',
        'requires_vot' => 'boolean',
        'is_active' => 'boolean',
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