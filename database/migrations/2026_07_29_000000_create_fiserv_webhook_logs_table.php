<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiserv_webhook_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('transaction_id')->nullable()->index();
            $table->string('order_id')->nullable()->index();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('transaction_state', 30)->nullable();
            $table->enum('status', ['processed', 'duplicate', 'failed', 'declined', 'invalid_signature'])->index();
            $table->text('error')->nullable();
            $table->json('raw_payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiserv_webhook_logs');
    }
};
