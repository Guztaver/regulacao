<?php

namespace Database\Factories;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Entry>
 */
class EntryFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => \App\Models\Patient::factory(),
            'title' => $this->faker->sentence(),
            'created_by' => \App\Models\User::factory(),
        ];
    }
}
