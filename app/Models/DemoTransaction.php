<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A local record of every charge/refund/inquire attempted through the demo
 * UI — separate from the package's own WebhookLog, which only records
 * inbound webhook notifications. This gives the test harness a visible
 * history of everything it has sent to the sandbox, success or failure.
 */
class DemoTransaction extends Model
{
    protected $fillable = [
        'action',
        'transaction_id',
        'order_id',
        'amount',
        'approved',
        'status_label',
        'failure_reason',
        'raw',
    ];

    protected $casts = [
        'approved' => 'boolean',
        'amount' => 'decimal:2',
        'raw' => 'array',
    ];
}
