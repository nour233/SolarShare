<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('pickup_points', 'slot_duration_minutes')) {
            Schema::table('pickup_points', function (Blueprint $table) {
                $table->unsignedSmallInteger('slot_duration_minutes')
                    ->default(30)
                    ->after('capacity');
            });
        }

        if (! Schema::hasIndex('deliveries', 'deliveries_pickup_capacity_idx')) {
            Schema::table('deliveries', function (Blueprint $table) {
                $table->index(
                    ['pickup_point_id', 'scheduled_date', 'type', 'status'],
                    'deliveries_pickup_capacity_idx'
                );
            });
        }

        if (! Schema::hasTable('pickup_point_opening_hours')) {
            Schema::create('pickup_point_opening_hours', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pickup_point_id')
                    ->constrained()
                    ->cascadeOnDelete();
                $table->unsignedTinyInteger('day_of_week');
                $table->time('opens_at');
                $table->time('closes_at');
                $table->timestamps();

                $table->index(['pickup_point_id', 'day_of_week', 'opens_at']);
            });
        }

        if (! Schema::hasTable('pickup_point_closures')) {
            Schema::create('pickup_point_closures', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pickup_point_id')
                    ->constrained()
                    ->cascadeOnDelete();
                $table->date('closure_date');
                $table->string('reason', 255);
                $table->timestamps();

                $table->unique(['pickup_point_id', 'closure_date']);
                $table->index(['closure_date', 'pickup_point_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_point_closures');
        Schema::dropIfExists('pickup_point_opening_hours');

        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropIndex('deliveries_pickup_capacity_idx');
        });

        Schema::table('pickup_points', function (Blueprint $table) {
            $table->dropColumn('slot_duration_minutes');
        });
    }
};
