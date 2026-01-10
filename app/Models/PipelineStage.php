<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PipelineStage extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'pipeline_id',
        'name',
        'description',
        'color',
        'sort_order',
        'is_default_stage',
    ];

    protected $casts = [
        'pipeline_id' => 'integer',
        'sort_order' => 'integer',
        'is_default_stage' => 'boolean',
    ];

    /**
     * Get the pipeline this stage belongs to
     */
    public function pipeline()
    {
        return $this->belongsTo(Pipeline::class, 'pipeline_id');
    }

    /**
     * Get all deals in this stage
     */
    public function deals()
    {
        return $this->hasMany(Deal::class, 'pipeline_stage_id');
    }
}
