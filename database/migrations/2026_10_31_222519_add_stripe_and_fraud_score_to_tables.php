<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('stripe_session_id')->nullable()->after('rental_id');
            $table->string('stripe_payment_intent')->nullable()->after('stripe_session_id');
            $table->string('invoice_number')->nullable()->unique()->after('stripe_payment_intent');
        });

        Schema::table('rentals', function (Blueprint $table) {
            $table->unsignedTinyInteger('risk_score')->nullable()->after('notes');
            $table->string('risk_level', 20)->nullable()->after('risk_score'); // 'low', 'medium', 'high'
            $table->json('risk_factors')->nullable()->after('risk_level');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['stripe_session_id', 'stripe_payment_intent', 'invoice_number']);
        });

        Schema::table('rentals', function (Blueprint $table) {
            $table->dropColumn(['risk_score', 'risk_level', 'risk_factors']);
        });
    }
};
