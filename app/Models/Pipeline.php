<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pipeline extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'name',
        'description',
        'color',
        'is_default',
        'sort_order',
        'created_by',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'sort_order' => 'integer',
        'created_by' => 'integer',
    ];

    /**
     * Get all stages for this pipeline
     */
    public function stages()
    {
        return $this->hasMany(PipelineStage::class, 'pipeline_id')->orderBy('sort_order');
    }

    /**
     * Get all deals in this pipeline
     */
    public function deals()
    {
        return $this->hasMany(Deal::class, 'pipeline_id');
    }

    /**
     * Get the user who created this pipeline
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
