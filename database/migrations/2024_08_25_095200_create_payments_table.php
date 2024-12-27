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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('amount');
            $table->date('payment_date');
            $table->enum('payment_type', ['Income', 'Expense']);
            $table->string('payment_method'); // e.g., bank transfer, credit card
            $table->text('note')->nullable();
            $table->nullableMorphs('paymentable');
            $table->foreignId('currency_id')->constrained('currencies');
            $table->unsignedBigInteger('exchange_rate'); // Rate used for conversion
            $table->unsignedBigInteger('amount_in_document_currency'); // Converted amount in document's currency
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
