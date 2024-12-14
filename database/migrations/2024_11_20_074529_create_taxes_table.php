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
        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "VAT", "Sales Tax"
            $table->enum('tax_computation', ['Fixed', 'Percentage', 'Group', 'Percentage_inclusive']);
            $table->unsignedInteger('amount')->nullable(); // Tax rate as a percentage or fixed
            $table->enum('type', ['Sales', 'Purchases', 'None']); // Determines how the tax is applied
            $table->foreignId('parent_id')->nullable()->constrained('taxes')->onDelete('cascade'); // Parent tax ID for grouping
            $table->enum('tax_scope', ['Goods', 'Services'])->default('Goods');
            $table->enum('status', ['Active', 'Inactive']); // Determines how the tax is applied
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taxes');
    }
};
