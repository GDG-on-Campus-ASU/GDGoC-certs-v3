<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Certificate>
 */
class CertificateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'unique_id' => fake()->uuid(),
            'recipient_name' => fake()->name(),
            'recipient_email' => fake()->safeEmail(),
            'state' => fake()->randomElement(['attending', 'completing']),
            'event_type' => fake()->randomElement(['workshop', 'course']),
            'event_title' => fake()->sentence(3),
            'issue_date' => fake()->date(),
            'issuer_name' => fake()->name(),
            'org_name' => fake()->company(),
            'status' => 'issued',
        ];
    }
}
