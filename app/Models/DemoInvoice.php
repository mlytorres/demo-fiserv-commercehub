<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A minimal invoice used to demonstrate the "invoice with a payment link" flow
 * against Commerce Hub's Hosted Checkout SDK. The `public_token` is the
 * long-lived link — Commerce Hub's own session behind it is minted fresh each
 * time /pay/{public_token} is opened (see FiservPaymentLinkController).
 */
class DemoInvoice extends Model
{
    protected $fillable = [
        'public_token',
        'description',
        'amount',
        'status',
        'transaction_id',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $invoice): void {
            $invoice->public_token ??= Str::random(10);
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_token';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
