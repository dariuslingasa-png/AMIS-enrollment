<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowRun extends Model
{
    protected $fillable = [
        'workflow_id', 'applicant_id', 'current_node_key', 'status',
        'context', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'context' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WorkflowRunLog::class, 'workflow_run_id');
    }

    public function applicant()
    {
        return $this->belongsTo(EnrollmentApplicant::class, 'applicant_id');
    }
}
