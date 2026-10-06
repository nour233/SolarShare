<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('categories', function(Blueprint $table) {
            $table->id(); $table->string('name')->unique(); $table->text('description')->nullable(); $table->string('icon')->default('fa-solar-panel'); $table->timestamps();
        });
        Schema::create('equipment', function(Blueprint $table) {
            $table->id(); $table->string('title'); $table->text('description');
            $table->decimal('power_capacity',12,2)->nullable(); $table->enum('power_unit',['W','Wh'])->default('W');
            $table->string('condition'); $table->decimal('price_per_day',10,2); $table->decimal('deposit',10,2)->default(0); $table->json('photos')->nullable();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete(); $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('equipment'); Schema::dropIfExists('categories'); }
};
