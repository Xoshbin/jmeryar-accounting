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
        Schema::create('taxables', function (Blueprint $table) {
            $table->id();
            $table->morphs('taxable'); // Creates `taxable_id` (bigint) and `taxable_type` (string) columns
            $table->foreignId('tax_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('tax_amount')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taxables');
    }
};
