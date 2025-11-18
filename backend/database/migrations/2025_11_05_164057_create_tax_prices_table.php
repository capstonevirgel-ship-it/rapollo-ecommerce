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
        Schema::create('tax_prices', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., "VAT", "GST", "Sales Tax"
            $table->decimal('rate', 5, 2); // Percentage (e.g., 8.00 for 8%)
            $table->text('description')->nullable(); // Optional description
            $table->boolean('is_active')->default(true); // Enable/disable this tax
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_prices');
    }
};
