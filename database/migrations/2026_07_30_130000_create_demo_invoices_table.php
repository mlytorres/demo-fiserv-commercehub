<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A minimal "invoice" so the demo can show a real invoice-with-payment-link
     * flow: create an invoice, get a long-lived link, open it later, pay it via
     * Commerce Hub's Hosted Checkout SDK. Commerce Hub itself has no native
     * "Pay by Link" yet (Fiserv docs list it as "Coming soon"), so the link
     * here is our own URL — the Commerce Hub session behind it is minted fresh
     * each time the link is opened, not stored.
     */
    public function up(): void
    {
        Schema::create('demo_invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('public_token')->unique();
            $table->string('description')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending'); // pending, paid, failed
            $table->string('transaction_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_invoices');
    }
};
