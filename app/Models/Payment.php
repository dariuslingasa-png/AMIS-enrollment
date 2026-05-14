<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'enrollment_applicant_id',
        'method',
        'reference_no',
        'amount',
        'receipt_url',
        'status',
        'remarks',
        'paid_at',
        'verified_at',
    ];

    protected $casts = [
        'paid_at'     => 'datetime',
        'verified_at' => 'datetime',
        'amount'      => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(EnrollmentApplicant::class, 'enrollment_applicant_id');
    }

    public function getMethodLabelAttribute(): string
    {
        return match($this->method) {
            'gcash' => 'GCash',
            'maya'  => 'Maya',
            'bdo'   => 'BDO Bank Transfer',
            default => ucfirst($this->method),
        };
    }
}
