<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Local history of every charge/refund/inquire attempted through this demo
     * app's UI — not part of the laravel-fiserv-commercehub package itself,
     * just a convenience log for the sandbox test harness.
     */
    public function up(): void
    {
        Schema::create('demo_transactions', function (Blueprint $table): void {
            $table->id();
            $table->string('action'); // charge, refund, inquire
            $table->string('transaction_id')->nullable()->index();
            $table->string('order_id')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->boolean('approved')->default(false);
            $table->string('status_label')->nullable();
            $table->text('failure_reason')->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_transactions');
    }
};
