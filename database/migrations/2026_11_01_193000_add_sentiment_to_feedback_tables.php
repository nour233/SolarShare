<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->string('sentiment_label', 20)->nullable()->after('comment');
            $table->decimal('sentiment_score', 4, 3)->nullable()->after('sentiment_label');
            $table->string('sentiment_source', 20)->nullable()->after('sentiment_score');
        });

        Schema::table('reclamations', function (Blueprint $table) {
            $table->string('sentiment_label', 20)->nullable()->after('description');
            $table->decimal('sentiment_score', 4, 3)->nullable()->after('sentiment_label');
            $table->string('sentiment_source', 20)->nullable()->after('sentiment_score');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn(['sentiment_label', 'sentiment_score', 'sentiment_source']);
        });

        Schema::table('reclamations', function (Blueprint $table) {
            $table->dropColumn(['sentiment_label', 'sentiment_score', 'sentiment_source']);
        });
    }
};
