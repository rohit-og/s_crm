<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactGroup extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'name',
        'description',
        'color',
        'created_by',
    ];

    protected $casts = [
        'created_by' => 'integer',
    ];

    /**
     * Get all clients in this group
     */
    public function clients()
    {
        return $this->belongsToMany(Client::class, 'client_contact_groups', 'contact_group_id', 'client_id')
            ->withTimestamps();
    }

    /**
     * Get the user who created this group
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
