<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('rfm_segment')->default('new')->after('role');
            $table->integer('rfm_recency_score')->nullable()->after('rfm_segment');
            $table->integer('rfm_frequency_score')->nullable()->after('rfm_recency_score');
            $table->integer('rfm_monetary_score')->nullable()->after('rfm_frequency_score');
            $table->decimal('rfm_total_score', 5, 2)->nullable()->after('rfm_monetary_score');
            $table->decimal('rfm_total_spent', 10, 2)->default(0)->after('rfm_total_score');
            $table->integer('rfm_rental_count')->default(0)->after('rfm_total_spent');
            $table->integer('rfm_days_since_last')->nullable()->after('rfm_rental_count');
            $table->timestamp('rfm_segmented_at')->nullable()->after('rfm_days_since_last');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'rfm_segment',
                'rfm_recency_score',
                'rfm_frequency_score',
                'rfm_monetary_score',
                'rfm_total_score',
                'rfm_total_spent',
                'rfm_rental_count',
                'rfm_days_since_last',
                'rfm_segmented_at',
            ]);
        });
    }
};

