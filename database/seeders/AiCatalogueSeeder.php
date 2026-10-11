<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Equipment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AiCatalogueSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CategorySeeder::class);
        $owner = User::firstOrCreate(['email' => 'demo-owner@solarshare.example'], ['name' => 'SolarShare Démonstration', 'password' => Str::random(48)]);
        $groups = [
            ['Panneaux solaires', 'solar', 'W', [
                ['Chargeur solaire randonnée 15 W', 15, 4, 25, 'Petit panneau de randonnée avec sortie USB 5 V pour téléphone et GPS. Production variable selon soleil ; aucun stockage intégré.'],
                ['Panneau USB bivouac 25 W', 25, 6, 35, 'Panneau pliable USB pour téléphone et lampe rechargeable lors d’un bivouac. Recharge uniquement en présence de soleil.'],
                ['Panneau pliable camping 60 W', 60, 10, 70, 'Panneau nomade pour camping. Sortie DC ; vérifier tension et connecteur de la station avant branchement.'],
                ['Panneau solaire van 120 W', 120, 16, 110, 'Panneau pour van et fourgon aménagé. Installation avec régulateur compatible nécessaire ; pas de batterie incluse.'],
                ['Panneau pliable station 160 W', 160, 19, 130, 'Panneau transportable pour recharger une station compatible pendant un séjour au camping. Connectique à vérifier.'],
                ['Kit solaire caravane 250 W', 250, 27, 180, 'Kit panneau et régulateur pour caravane. Batterie non incluse ; installation et tension à vérifier.'],
                ['Panneau rigide atelier 350 W', 350, 30, 220, 'Panneau rigide pour petite installation fixe dans un atelier. Régulateur et stockage séparés.'],
                ['Kit solaire jardin 400 W', 400, 38, 260, 'Deux panneaux pour un abri de jardin. Nécessite stockage et régulation adaptés aux appareils utilisés.'],
                ['Kit solaire démonstration 600 W', 600, 48, 350, 'Ensemble pédagogique pour atelier découverte photovoltaïque. Montage à réaliser par une personne compétente.'],
                ['Installation solaire mobile 800 W', 800, 60, 450, 'Ensemble de panneaux transportables pour événement extérieur. Production dépendante du soleil, sans stockage intégré.'],
            ]],
            ['Batteries', 'battery', 'Wh', [
                ['Batterie USB randonnée 74 Wh', 74, 4, 25, 'Batterie USB avec sortie USB-C 20 W pour téléphone et GPS. Aucun branchement secteur 230 V.'],
                ['Batterie téléphone week-end 150 Wh', 150, 7, 45, 'Réserve d’énergie USB-C 45 W pour téléphones et lampes rechargeables au camping. Autonomie selon consommation.'],
                ['Station compacte camping 300 Wh', 300, 12, 90, 'Station avec USB et sortie 230 V limitée à 300 W, pour petit matériel de camping. Ne convient pas au chauffage électrique.'],
                ['Station télétravail 600 Wh', 600, 22, 160, 'Station USB-C 100 W et sortie 230 V 600 W pour ordinateur portable et routeur. Vérifier la consommation totale.'],
                ['Station photo nomade 750 Wh', 750, 26, 190, 'Station pour recharge d’appareils photo, drones et ordinateurs. USB-C et sortie secteur 800 W ; chargeurs non inclus.'],
                ['Station van autonome 1200 Wh', 1200, 38, 280, 'Station 230 V 1200 W pour fourgon et appareils compatibles. Recharge solaire possible via panneau compatible non inclus.'],
                ['Station secours maison 1500 Wh', 1500, 45, 340, 'Réserve pour routeur, éclairage et petits appareils, sortie 230 V 1500 W. Aucun raccordement direct au tableau électrique.'],
                ['Station événement extérieur 2000 Wh', 2000, 60, 420, 'Station pour petit événement : éclairage LED et sonorisation compatible. Puissance continue maximum 2000 W.'],
                ['Stockage solaire atelier 3000 Wh', 3000, 78, 550, 'Station 230 V 2400 W pour atelier mobile. Vérifier les pointes de démarrage des outils avant utilisation.'],
                ['Stockage grande capacité 5000 Wh', 5000, 110, 800, 'Réserve d’énergie pour événement prolongé, sortie 230 V 3000 W. Transport à prévoir ; autonomie à calculer selon les charges.'],
            ]],
            ['Petites éoliennes', 'wind', 'W', [
                ['Éolienne pédagogique 20 W', 20, 8, 50, 'Maquette de démonstration du vent pour atelier scolaire. Usage pédagogique, pas pour alimenter un logement.'],
                ['Micro-éolienne découverte 50 W', 50, 12, 75, 'Petite éolienne pour expérimentations énergétiques. Régulateur et stockage nécessaires ; production variable selon vent.'],
                ['Éolienne jardin 100 W', 100, 18, 120, 'Éolienne expérimentale pour terrain dégagé. Installation sécurisée, mât et régulation adaptés nécessaires.'],
                ['Éolienne site isolé 200 W', 200, 24, 160, 'Petit système pour production sur site venteux. Ne remplace pas une réserve d’énergie ; batterie non incluse.'],
                ['Éolienne nomade démonstration 350 W', 350, 32, 210, 'Éolienne pour exposition et démonstration extérieure. Puissance nominale non garantie sans conditions de vent adaptées.'],
                ['Éolienne abri rural 450 W', 450, 39, 250, 'Production éolienne pour projet expérimental en zone dégagée. Vérifier conditions locales et installation.'],
                ['Kit éolien terrain 600 W', 600, 48, 320, 'Kit pour terrain venteux avec régulateur compatible à prévoir. Stockage et installation non inclus.'],
                ['Éolienne atelier énergie 750 W', 750, 55, 380, 'Équipement de formation aux énergies renouvelables. Montage par personne compétente et espace de sécurité requis.'],
                ['Éolienne petite ferme 900 W', 900, 65, 450, 'Éolienne pour étude de production en milieu rural. Mât, stockage et protections à dimensionner séparément.'],
                ['Kit éolien site venteux 1200 W', 1200, 85, 600, 'Installation éolienne expérimentale pour site dégagé. Production intermittente ; vérifier faisabilité et sécurité avant location.'],
            ]],
        ];
        foreach ($groups as [$category, $photoType, $unit, $items]) {
            $numbers = match ($photoType) {
                'solar' => [9, 8, 12, 2, 1, 10, 7],
                'battery' => [11, 12, 2, 1, 3, 6, 7, 10],
                'wind' => [7, 9, 1, 2, 3, 4, 5, 6, 8, 10],
            };
            $photos = array_map(fn ($number) => public_path('front/img/demo-catalogue/'.$photoType.'-variety-'.$number.'.jpg'), $numbers);
            $credits = collect(json_decode(file_get_contents(base_path('docs/demo-photo-sources.json')), true))->keyBy('file');
            if (! $photos) throw new \RuntimeException('Photos manquantes pour '.$photoType);
            foreach ($items as $index => [$title, $capacity, $price, $deposit, $description]) {
                $photo = basename($photos[$index % count($photos)]);
                $credit = $credits[$photo];
                Equipment::updateOrCreate(['title' => $title, 'owner_id' => $owner->id], [
                    'description' => $description.' Fiche fictive de démonstration ; photo illustrative, modèle non contractuel.',
                    'category_id' => Category::where('name', $category)->firstOrFail()->id,
                    'power_capacity' => $capacity, 'power_unit' => $unit,
                    'condition' => $index % 2 ? 'Bon état' : 'Très bon état',
                    'price_per_day' => $price, 'deposit' => $deposit,
                    'photos' => ['front/img/demo-catalogue/'.$photo],
                ]);
            }
        }
    }
}
