<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');           // human-readable name shown in admin
            $table->string('group');           // weekend | peak | discount
            $table->decimal('value', 8, 4);   // e.g. 0.15 for 15%
            $table->string('unit')->default('percent'); // percent | days
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Seed default values
        DB::table('pricing_rules')->insert([
            // Weekend surcharge
            [
                'key'         => 'weekend_surcharge',
                'label'       => 'Supplément week-end',
                'group'       => 'weekend',
                'value'       => 0.15,
                'unit'        => 'percent',
                'description' => 'Supplément appliqué sur chaque jour de week-end (samedi & dimanche).',
                'created_at'  => now(), 'updated_at' => now(),
            ],
            // Peak season surcharge
            [
                'key'         => 'peak_surcharge',
                'label'       => 'Supplément haute saison',
                'group'       => 'peak',
                'value'       => 0.10,
                'unit'        => 'percent',
                'description' => 'Supplément appliqué sur les jours en haute saison (mois configurés ci-dessous).',
                'created_at'  => now(), 'updated_at' => now(),
            ],
            // Peak months stored as comma-separated month numbers
            [
                'key'         => 'peak_months',
                'label'       => 'Mois de haute saison',
                'group'       => 'peak',
                'value'       => 6,           // stored separately per month, we use a different approach
                'unit'        => 'months',
                'description' => 'Valeur JSON stockée dans la colonne description.',
                'created_at'  => now(), 'updated_at' => now(),
            ],
            // Duration discounts
            [
                'key'         => 'discount_7_days',
                'label'       => 'Réduction 7+ jours',
                'group'       => 'discount',
                'value'       => 0.10,
                'unit'        => 'percent',
                'description' => 'Réduction appliquée à partir de 7 jours de location.',
                'created_at'  => now(), 'updated_at' => now(),
            ],
            [
                'key'         => 'discount_14_days',
                'label'       => 'Réduction 14+ jours',
                'group'       => 'discount',
                'value'       => 0.15,
                'unit'        => 'percent',
                'description' => 'Réduction appliquée à partir de 14 jours de location.',
                'created_at'  => now(), 'updated_at' => now(),
            ],
            [
                'key'         => 'discount_30_days',
                'label'       => 'Réduction 30+ jours',
                'group'       => 'discount',
                'value'       => 0.20,
                'unit'        => 'percent',
                'description' => 'Réduction appliquée à partir de 30 jours de location.',
                'created_at'  => now(), 'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
