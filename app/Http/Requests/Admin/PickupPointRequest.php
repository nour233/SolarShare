<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PickupPointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'city' => ['required', 'string', 'min:2', 'max:255'],
            'address' => ['required', 'string', 'min:5', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'opening_hours' => ['required', 'string', 'min:3', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1', 'max:10000'],
            'slot_duration_minutes' => ['sometimes', 'integer', 'between:15,120'],
            'status' => ['required', 'in:active,inactive'],
            'weekly_schedule' => ['sometimes', 'array'],
            'weekly_schedule.*' => ['array'],
            'weekly_schedule.*.closed' => ['required_with:weekly_schedule', 'boolean'],
            'weekly_schedule.*.intervals' => ['sometimes', 'array'],
            'weekly_schedule.*.intervals.*.opens_at' => ['required', 'date_format:H:i'],
            'weekly_schedule.*.intervals.*.closes_at' => ['required', 'date_format:H:i'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $schedule = $this->input('weekly_schedule');

            if (! is_array($schedule)) {
                return;
            }

            foreach (range(1, 7) as $day) {
                if (! array_key_exists($day, $schedule)) {
                    $validator->errors()->add(
                        "weekly_schedule.{$day}.closed",
                        'Veuillez définir explicitement chaque jour de la semaine.'
                    );

                    continue;
                }

                $dayData = $schedule[$day];
                if (! is_array($dayData)) {
                    continue;
                }

                $intervals = collect($dayData['intervals'] ?? [])
                    ->filter(fn ($interval) => is_array($interval)
                        && filled($interval['opens_at'] ?? null)
                        && filled($interval['closes_at'] ?? null))
                    ->values();

                if (filter_var($dayData['closed'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                    if ($intervals->isNotEmpty()) {
                        $validator->errors()->add(
                            "weekly_schedule.{$day}.intervals",
                            'Un jour fermé ne peut pas contenir d’intervalle.'
                        );
                    }

                    continue;
                }

                if ($intervals->isEmpty()) {
                    $validator->errors()->add(
                        "weekly_schedule.{$day}.intervals",
                        'Ajoutez au moins un intervalle ou marquez ce jour comme fermé.'
                    );

                    continue;
                }

                $sortedIntervals = $intervals
                    ->sortBy('opens_at')
                    ->values();

                for ($index = 0; $index < $sortedIntervals->count(); $index++) {
                    $interval = $sortedIntervals[$index];

                    if ($interval['opens_at'] >= $interval['closes_at']) {
                        $validator->errors()->add(
                            "weekly_schedule.{$day}.intervals.{$index}.closes_at",
                            'L’heure de fermeture doit être après l’heure d’ouverture.'
                        );
                    }

                    if (
                        $index > 0
                        && $sortedIntervals[$index - 1]['closes_at'] > $interval['opens_at']
                    ) {
                        $validator->errors()->add(
                            "weekly_schedule.{$day}.intervals.{$index}.opens_at",
                            'Les intervalles d’une même journée ne peuvent pas se chevaucher.'
                        );
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du point de retrait est obligatoire.',
            'name.min' => 'Le nom doit contenir au moins 3 caractères.',
            'name.max' => 'Le nom ne peut pas dépasser 255 caractères.',
            'city.required' => 'La ville est obligatoire.',
            'city.min' => 'La ville doit contenir au moins 2 caractères.',
            'city.max' => 'La ville ne peut pas dépasser 255 caractères.',
            'address.required' => 'L’adresse est obligatoire.',
            'address.min' => 'L’adresse doit contenir au moins 5 caractères.',
            'address.max' => 'L’adresse ne peut pas dépasser 255 caractères.',
            'latitude.required' => 'Veuillez sélectionner un emplacement sur la carte.',
            'latitude.numeric' => 'La latitude doit être valide.',
            'latitude.between' => 'La latitude doit être comprise entre -90 et 90.',
            'longitude.required' => 'Veuillez sélectionner un emplacement sur la carte.',
            'longitude.numeric' => 'La longitude doit être valide.',
            'longitude.between' => 'La longitude doit être comprise entre -180 et 180.',
            'opening_hours.required' => 'Les horaires d’ouverture sont obligatoires.',
            'opening_hours.min' => 'Veuillez saisir des horaires valides.',
            'capacity.required' => 'La capacité est obligatoire.',
            'capacity.integer' => 'La capacité doit être un nombre entier.',
            'capacity.min' => 'La capacité doit être supérieure à 0.',
            'capacity.max' => 'La capacité ne peut pas dépasser 10 000.',
            'status.required' => 'Le statut est obligatoire.',
            'status.in' => 'Le statut sélectionné est invalide.',
            'slot_duration_minutes.between' => 'La durée d’un créneau doit être comprise entre 15 et 120 minutes.',
            'weekly_schedule.*.intervals.*.opens_at.required' => 'L’heure d’ouverture est obligatoire.',
            'weekly_schedule.*.intervals.*.opens_at.date_format' => 'L’heure d’ouverture doit être au format HH:MM.',
            'weekly_schedule.*.intervals.*.closes_at.required' => 'L’heure de fermeture est obligatoire.',
            'weekly_schedule.*.intervals.*.closes_at.date_format' => 'L’heure de fermeture doit être au format HH:MM.',
        ];
    }
}
