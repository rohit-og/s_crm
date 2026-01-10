<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormSubmission extends Model
{
    protected $fillable = [
        'form_id',
        'client_id',
        'data',
        'ip_address',
        'user_agent',
        'submitted_at',
    ];

    protected $casts = [
        'form_id' => 'integer',
        'client_id' => 'integer',
        'data' => 'array',
        'submitted_at' => 'datetime',
    ];

    /**
     * Get the form this submission belongs to
     */
    public function form()
    {
        return $this->belongsTo(CrmForm::class, 'form_id');
    }

    /**
     * Get the client this submission is matched to (if any)
     */
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
