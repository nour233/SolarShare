<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 10, 2);
            $table->string('method')->default('card'); // card, cash, transfer
            $table->enum('status', ['pending', 'paid', 'refunded'])->default('pending');
            $table->string('type')->default('rental'); // rental | deposit
            $table->foreignId('rental_id')->constrained('rentals')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
