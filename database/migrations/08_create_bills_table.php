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
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->string('bill_number')->unique();
            $table->date('bill_date');
            $table->date('due_date')->nullable();
            $table->unsignedBigInteger('total_amount');
            $table->unsignedBigInteger('total_paid_amount')->nullable();
            $table->unsignedBigInteger('amount_due')->nullable();
            $table->unsignedBigInteger('untaxed_amount');
            $table->unsignedBigInteger('tax_amount')->nullable();
            $table->enum('status', ['Draft', 'Received', 'Partial', 'Paid'])->default('draft');
            $table->text('note')->nullable();
            $table->foreignId('expense_account_id')->constrained('accounts')->onDelete('cascade');
            $table->foreignId('liability_account_id')->constrained('accounts')->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->foreignId('currency_id')->constrained('currencies')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
