<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Deal extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'name',
        'description',
        'client_id',
        'pipeline_id',
        'pipeline_stage_id',
        'value',
        'currency',
        'expected_close_date',
        'actual_close_date',
        'probability',
        'status',
        'assigned_to',
        'created_by',
    ];

    protected $casts = [
        'client_id' => 'integer',
        'pipeline_id' => 'integer',
        'pipeline_stage_id' => 'integer',
        'value' => 'decimal:2',
        'probability' => 'integer',
        'assigned_to' => 'integer',
        'created_by' => 'integer',
        'expected_close_date' => 'date',
        'actual_close_date' => 'date',
    ];

    /**
     * Get the client this deal belongs to
     */
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * Get the pipeline this deal belongs to
     */
    public function pipeline()
    {
        return $this->belongsTo(Pipeline::class, 'pipeline_id');
    }

    /**
     * Get the pipeline stage this deal is in
     */
    public function stage()
    {
        return $this->belongsTo(PipelineStage::class, 'pipeline_stage_id');
    }

    /**
     * Get the user assigned to this deal
     */
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who created this deal
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all followups for this deal
     */
    public function followups()
    {
        return $this->hasMany(Followup::class, 'deal_id');
    }
}
