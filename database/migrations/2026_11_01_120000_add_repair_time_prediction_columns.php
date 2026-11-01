<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Prediction shown to the client: {"days", "min", "max", "source"}.
        Schema::table('incidents', function (Blueprint $table) {
            $table->json('repair_estimate')->nullable()->after('ai_diagnosis');
        });

        // Real repair time, used to retrain the model on our own history.
        Schema::table('maintenances', function (Blueprint $table) {
            $table->foreignId('incident_id')->nullable()->after('equipment_id')->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('duration_days')->nullable()->after('cost');
        });
    }

    public function down(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('incident_id');
            $table->dropColumn('duration_days');
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->dropColumn('repair_estimate');
        });
    }
};
