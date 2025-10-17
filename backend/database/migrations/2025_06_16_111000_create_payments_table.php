<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('purchase_id')->constrained();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('PHP');
            $table->enum('status', ['pending', 'processing', 'paid', 'failed', 'cancelled', 'expired'])->default('pending');
            $table->string('payment_method')->nullable(); // card, gcash, paymaya, cod
            $table->string('payment_intent_id')->nullable(); // PayMongo payment intent ID
            $table->string('transaction_id')->nullable(); // Payment transaction ID
            $table->string('payment_method_id')->nullable(); // Payment method ID
            $table->string('payment_failure_code')->nullable(); // Payment failure code
            $table->text('payment_failure_message')->nullable(); // Payment failure message
            $table->timestamp('payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Store additional payment data
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
