<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmForm extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'name',
        'description',
        'form_fields',
        'submit_button_text',
        'success_message',
        'redirect_url',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'form_fields' => 'array',
        'is_active' => 'boolean',
        'created_by' => 'integer',
    ];

    /**
     * Get all submissions for this form
     */
    public function submissions()
    {
        return $this->hasMany(FormSubmission::class, 'form_id');
    }

    /**
     * Get the user who created this form
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
