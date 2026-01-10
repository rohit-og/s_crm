<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Followup extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at', 'scheduled_at', 'completed_at', 'reminder_at'];

    protected $fillable = [
        'deal_id',
        'client_id',
        'type',
        'subject',
        'description',
        'scheduled_at',
        'completed_at',
        'status',
        'assigned_to',
        'created_by',
        'reminder_at',
    ];

    protected $casts = [
        'deal_id' => 'integer',
        'client_id' => 'integer',
        'assigned_to' => 'integer',
        'created_by' => 'integer',
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'reminder_at' => 'datetime',
    ];

    /**
     * Get the deal this followup is related to (optional)
     */
    public function deal()
    {
        return $this->belongsTo(Deal::class, 'deal_id');
    }

    /**
     * Get the client this followup is for
     */
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * Get the user assigned to this followup
     */
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who created this followup
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
