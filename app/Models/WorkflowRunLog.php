<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowRunLog extends Model
{
    protected $fillable = [
        'workflow_run_id', 'node_key', 'node_type',
        'status', 'message', 'output', 'executed_at',
    ];

    protected $casts = [
        'output' => 'array',
        'executed_at' => 'datetime',
    ];

    public function run()
    {
        return $this->belongsTo(WorkflowRun::class, 'workflow_run_id');
    }
}
