<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents a versioned document attachment on a request.
 *
 * NOTE: This model currently has no migration. If you need file versioning,
 * create the migration first:
 *   php artisan make:migration create_documents_table
 *
 * Until then, the single `file_path` column on the requests table is used
 * and the Request::documents() relationship will throw if called.
 * The relationship is commented out below until the table exists.
 */
class Document extends Model
{
    protected $fillable = [
        'request_id',
        'file_path',
        'original_name',
        'uploaded_by',
    ];

    // public function request()
    // {
    //     return $this->belongsTo(Request::class);
    // }
}
