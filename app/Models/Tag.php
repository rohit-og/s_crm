<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tag extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'name',
        'color',
        'created_by',
    ];

    protected $casts = [
        'created_by' => 'integer',
    ];

    /**
     * Get all clients with this tag
     */
    public function clients()
    {
        return $this->belongsToMany(Client::class, 'client_tags', 'tag_id', 'client_id')
            ->withTimestamps();
    }

    /**
     * Get the user who created this tag
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
