<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('full_name', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('street', 255)->nullable();
            $table->string('barangay', 150)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 150)->nullable();
            $table->string('zipcode', 20)->nullable();
            $table->string('complete_address', 1000)->nullable();
            $table->string('country', 100)->nullable()->default('Philippines');
            $table->string('avatar_url', 255)->nullable();
            $table->timestamps(); 
        });
    }

    public function down()
    {
        Schema::dropIfExists('profiles');
    }
};