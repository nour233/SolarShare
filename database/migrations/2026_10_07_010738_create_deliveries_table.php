<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();

            $table->enum('type', [
                'pickup',
                'home_delivery',
            ]);

            $table->date('scheduled_date');
            $table->time('scheduled_time');

            $table->enum('status', [
                'planned',
                'in_transit',
                'delivered',
                'returned',
            ])->default('planned');

            $table->decimal('delivery_fee', 10, 2)->default(0);

            $table->foreignId('rental_id')
                ->constrained('rentals')
                ->cascadeOnDelete();

            $table->foreignId('pickup_point_id')
                ->nullable()
                ->constrained('pickup_points')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};