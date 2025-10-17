<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->decimal('total', 10, 2);
            $table->enum('status', ['pending', 'processing', 'completed', 'cancelled'])->default('pending');
            $table->enum('type', ['product', 'ticket'])->default('product'); // New field to distinguish purchase types
            $table->unsignedBigInteger('event_id')->nullable(); // For ticket purchases - foreign key will be added later
            $table->json('shipping_address')->nullable(); // For product purchases
            $table->json('billing_address')->nullable(); // For product purchases
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchases');
    }
};