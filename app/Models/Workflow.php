<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workflow extends Model
{
    protected $fillable = [
        'name', 'description', 'is_active', 'trigger_event',
        'trigger_conditions', 'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'trigger_conditions' => 'array',
    ];

    public function nodes(): HasMany
    {
        return $this->hasMany(WorkflowNode::class);
    }

    public function edges(): HasMany
    {
        return $this->hasMany(WorkflowEdge::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(WorkflowRun::class);
    }

    public function getTriggerNodeAttribute(): ?WorkflowNode
    {
        return $this->nodes()->where('type', 'trigger')->first();
    }
}
