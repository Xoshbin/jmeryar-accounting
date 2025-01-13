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
        Schema::create('bill_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quantity');
            $table->unsignedBigInteger('cost_price')->nullable();
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('total_cost');
            $table->unsignedBigInteger('untaxed_amount')->nullable();
            $table->unsignedBigInteger('tax_amount')->nullable();
            $table->foreignId('bill_id')->constrained('bills')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_items');
    }
};
