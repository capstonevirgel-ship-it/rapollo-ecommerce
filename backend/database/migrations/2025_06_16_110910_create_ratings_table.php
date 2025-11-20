<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('variant_id')->constrained('product_variants');
            $table->foreignId('product_purchase_id')->constrained('product_purchases');
            $table->integer('stars'); // 1-5 rating
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'variant_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('ratings');
    }
};