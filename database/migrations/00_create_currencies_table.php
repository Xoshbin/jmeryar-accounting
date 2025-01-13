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
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // e.g., "USD", "EUR"
            $table->string('name'); // e.g., "US Dollar", "Euro"
            $table->string('symbol'); // e.g., "$", "€"
            $table->string('currency_unit'); // e.g., "Dollar", "Dinar"
            $table->string('currency_subunit'); // e.g., "Cent", "Dinar"
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
