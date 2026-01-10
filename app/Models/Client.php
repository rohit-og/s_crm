<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $dates = ['deleted_at'];

    protected $fillable = [
        'name', 'code', 'adresse', 'email', 'phone', 'country', 'city', 'tax_number',
        'is_royalty_eligible', 'points', 'opening_balance', 'credit_limit',
        'company_name', 'job_title', 'source', 'assigned_agent_id',
    ];

    protected $casts = [
        'code' => 'integer',
        'is_royalty_eligible' => 'integer',
        'points' => 'double',
        'opening_balance' => 'double',
        'credit_limit' => 'double',
        'assigned_agent_id' => 'integer',
    ];

    /**
     * Get custom field values for this client
     */
    public function customFieldValues()
    {
        return $this->morphMany(CustomFieldValue::class, 'entity', 'entity_type', 'entity_id');
    }

    /**
     * Get all deals for this client
     */
    public function deals()
    {
        return $this->hasMany(Deal::class, 'client_id');
    }

    /**
     * Get all followups for this client
     */
    public function followups()
    {
        return $this->hasMany(Followup::class, 'client_id');
    }

    /**
     * Get all contact groups this client belongs to
     */
    public function contactGroups()
    {
        return $this->belongsToMany(ContactGroup::class, 'client_contact_groups', 'client_id', 'contact_group_id')
            ->withTimestamps();
    }

    /**
     * Get all tags for this client
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'client_tags', 'client_id', 'tag_id')
            ->withTimestamps();
    }

    /**
     * Get the assigned agent (user) for this client
     */
    public function assignedAgent()
    {
        return $this->belongsTo(User::class, 'assigned_agent_id');
    }

    /**
     * Get deals assigned to a specific agent (for this client)
     */
    public function assignedDeals()
    {
        return $this->hasMany(Deal::class, 'client_id')->whereNotNull('assigned_to');
    }
}
