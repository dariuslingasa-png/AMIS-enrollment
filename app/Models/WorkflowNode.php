<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowNode extends Model
{
    protected $fillable = [
        'workflow_id', 'node_key', 'type', 'label', 'config', 'position_x', 'position_y',
    ];

    protected $casts = [
        'config' => 'array',
        'position_x' => 'float',
        'position_y' => 'float',
    ];

    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }
}
