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
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('payment_status')->default('pending')->after('status');
            $table->string('payment_intent_id')->nullable()->after('payment_status');
            $table->decimal('payment_amount', 10, 2)->nullable()->after('payment_intent_id');
            $table->string('payment_currency', 3)->default('PHP')->after('payment_amount');
            $table->string('payment_failure_code')->nullable()->after('payment_currency');
            $table->text('payment_failure_message')->nullable()->after('payment_failure_code');
            $table->json('shipping_address')->nullable()->after('payment_failure_message');
            $table->json('billing_address')->nullable()->after('shipping_address');
            $table->timestamp('paid_at')->nullable()->after('billing_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn([
                'payment_status',
                'payment_intent_id',
                'payment_amount',
                'payment_currency',
                'payment_failure_code',
                'payment_failure_message',
                'shipping_address',
                'billing_address',
                'paid_at'
            ]);
        });
    }
};
