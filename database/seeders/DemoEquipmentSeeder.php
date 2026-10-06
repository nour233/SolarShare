<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Equipment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoEquipmentSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CategorySeeder::class);

        $owner = User::firstOrCreate(['email' => 'demo-owner@solarshare.example'], [
            'name' => 'SolarShare Démonstration',
            'password' => Str::random(48),
        ]);

        $items = [
            ['Panneaux solaires', 'Panneau solaire portable 100 W', 'Panneau pliable pour vos sorties et petits besoins en énergie.', 100, 'W', 'Très bon état', 15, 100, ['front/img/gallery-1.jpg']],
            ['Panneaux solaires', 'Kit solaire nomade 200 W', 'Kit de démonstration pour tester le catalogue et les fiches produits.', 200, 'W', 'Bon état', 25, 180, ['front/img/gallery-2.jpg']],
            ['Batteries', 'Station d’énergie portable 500 Wh', 'Batterie de démonstration pour alimenter de petits appareils en déplacement.', 500, 'Wh', 'Très bon état', 20, 150, []],
            ['Batteries', 'Batterie de stockage 1 000 Wh', 'Station de stockage pour découvrir la catégorie batteries.', 1000, 'Wh', 'Bon état', 35, 250, []],
            ['Petites éoliennes', 'Mini-éolienne 300 W', 'Équipement de démonstration pour tester les filtres du catalogue.', 300, 'W', 'Bon état', 30, 200, []],
            ['Petites éoliennes', 'Kit éolien compact 500 W', 'Kit de démonstration avec puissance, caution et propriétaire.', 500, 'W', 'Très bon état', 45, 300, []],
        ];

        foreach ($items as [$category, $title, $description, $power, $unit, $condition, $price, $deposit, $photos]) {
            Equipment::firstOrCreate(['title' => $title, 'owner_id' => $owner->id], [
                'description' => $description.' Données fictives pour les tests.',
                'power_capacity' => $power,
                'power_unit' => $unit,
                'condition' => $condition,
                'price_per_day' => $price,
                'deposit' => $deposit,
                'photos' => $photos,
                'category_id' => Category::where('name', $category)->firstOrFail()->id,
            ]);
        }
    }
}
