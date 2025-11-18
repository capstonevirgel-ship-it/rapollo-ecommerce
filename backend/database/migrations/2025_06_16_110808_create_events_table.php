<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 100);
            $table->text('description')->nullable();
            $table->date('date');
            $table->string('location', 100)->nullable();
            $table->string('poster_url', 255)->nullable();
            $table->decimal('base_ticket_price', 10, 2)->nullable(); // Base price before tax
            $table->decimal('ticket_price', 10, 2)->nullable(); // Final price (base_ticket_price + taxes)
            $table->integer('max_tickets')->nullable();
            $table->integer('available_tickets')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('events');
    }
};