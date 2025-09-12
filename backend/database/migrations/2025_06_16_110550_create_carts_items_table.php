<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->timestamp('added_at')->useCurrent();
            $table->timestamps(); // This adds created_at and updated_at columns

            $table->unique(['user_id', 'variant_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('cart_items');
    }
};