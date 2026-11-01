<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\Reclamation;
use App\Models\Rental;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DemoEquipmentSeeder::class);

        $clients = collect([
            ['name' => 'Nadia Benali', 'email' => 'nadia.demo@solarshare.test'],
            ['name' => 'Youssef Martin', 'email' => 'youssef.demo@solarshare.test'],
            ['name' => 'Claire Dubois', 'email' => 'claire.demo@solarshare.test'],
            ['name' => 'Omar Haddad', 'email' => 'omar.demo@solarshare.test'],
            ['name' => 'Sophie Leroy', 'email' => 'sophie.demo@solarshare.test'],
            ['name' => 'Karim Morel', 'email' => 'karim.demo@solarshare.test'],
        ])->map(function (array $attributes): User {
            return User::firstOrCreate(
                ['email' => $attributes['email']],
                [
                    'name' => $attributes['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
        });

        $equipment = Equipment::query()->where('title', 'like', '%de démonstration%')->get();
        if ($equipment->isEmpty()) {
            $equipment = Equipment::query()->limit(6)->get();
        }

        if ($equipment->isEmpty()) {
            $this->command?->warn('Aucun équipement disponible : les locations de démonstration n’ont pas été créées.');

            return;
        }

        $this->seedRentals($clients, $equipment);
        $this->seedReviews($clients);
        $this->seedReclamations($clients);

        $this->command?->info('Données de démonstration créées ou déjà présentes.');
        $this->command?->line('Mot de passe des comptes demo : password');
    }

    private function seedRentals($clients, $equipment): void
    {
        $rentals = [
            [$clients[0], $equipment[0], 'returned', -30, -25],
            [$clients[0], $equipment[1], 'ongoing', -2, 5],
            [$clients[1], $equipment[2], 'returned', -60, -55],
            [$clients[2], $equipment[3], 'accepted', 4, 9],
            [$clients[3], $equipment[4], 'cancelled', -90, -84],
            [$clients[4], $equipment[5] ?? $equipment[0], 'returned', -120, -114],
        ];

        foreach ($rentals as [$client, $item, $status, $startOffset, $endOffset]) {
            Rental::firstOrCreate(
                [
                    'renter_id' => $client->id,
                    'equipment_id' => $item->id,
                    'start_date' => Carbon::now()->addDays($startOffset)->toDateString(),
                ],
                [
                    'end_date' => Carbon::now()->addDays($endOffset)->toDateString(),
                    'total_price' => $item->price_per_day * (abs($endOffset - $startOffset) + 1),
                    'status' => $status,
                    'notes' => 'Location de démonstration.',
                ]
            );
        }
    }

    private function seedReviews($clients): void
    {
        $reviews = [
            [$clients[0], 'rental', 5, 'Location parfaite', 'Équipement propre, livraison rapide et équipe très professionnelle.'],
            [$clients[1], 'equipment', 4, 'Bon équipement', 'La batterie fonctionne bien et correspond à la description.'],
            [$clients[2], 'delivery', 2, 'Livraison en retard', 'Le matériel est bon mais la livraison a eu deux jours de retard.'],
            [$clients[3], 'maintenance', 1, 'Intervention décevante', 'Le problème n’a pas été résolu après la première intervention.'],
            [$clients[4], 'general', 5, 'Très satisfait', 'Une expérience simple et efficace, je recommande SolarShare.'],
        ];

        foreach ($reviews as [$client, $service, $rating, $title, $comment]) {
            Review::firstOrCreate(
                ['user_id' => $client->id, 'title' => $title],
                compact('service', 'rating', 'comment')
            );
        }
    }

    private function seedReclamations($clients): void
    {
        $reclamations = [
            [$clients[0], 'location', 'Question sur ma location', 'Je souhaite confirmer la date de retour de mon équipement.', 'resolue'],
            [$clients[0], 'livraison', 'Suivi de livraison', 'Je voudrais connaître l’avancement de ma livraison.', 'en_cours'],
            [$clients[0], 'maintenance', 'Demande de maintenance', 'Le rendement de mon équipement semble avoir diminué.', 'ouverte'],
            [$clients[1], 'equipement', 'Accessoire manquant', 'Un câble annoncé dans la description n’était pas dans le colis.', 'resolue'],
            [$clients[2], 'livraison', 'Retard de livraison', 'La livraison est arrivée en retard et cela a perturbé mon projet.', 'rejetee'],
            [$clients[3], 'avis', 'Désaccord avec un avis', 'Je souhaite signaler un problème concernant un avis publié.', 'ouverte'],
        ];

        foreach ($reclamations as [$client, $type, $subject, $description, $status]) {
            Reclamation::firstOrCreate(
                ['user_id' => $client->id, 'subject' => $subject],
                [
                    'type' => $type,
                    'description' => $description,
                    'reclamation_date' => Carbon::now()->subDays(random_int(1, 45))->toDateString(),
                    'status' => $status,
                ]
            );
        }
    }
}
