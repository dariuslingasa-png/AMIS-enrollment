<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowEdge extends Model
{
    protected $fillable = [
        'workflow_id', 'edge_key', 'source_node_key', 'target_node_key',
        'condition_value', 'label',
    ];

    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }
}
