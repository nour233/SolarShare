<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('equipment')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // the reporter
            // No foreign key yet: the rentals module does not exist.
            $table->unsignedBigInteger('rental_id')->nullable();
            $table->text('description');
            $table->string('severity'); // mineur | moyen | grave
            $table->json('photos')->nullable();
            $table->string('status')->default('ouvert'); // ouvert | en_cours | resolu | rejete
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
