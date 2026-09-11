<?php

namespace Database\Factories;

use App\Enums\PrivacyLevel;
use App\Models\Alter;
use App\Models\System;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Alter> */
class AlterFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->firstName();

        return [
            'system_id' => System::factory(),
            'name' => $name,
            // Suffixe en lettres : les chiffres se confondent avec des lettres
            // dans la forme canonique, deux handles générés pourraient entrer
            // en collision.
            'handle' => Str::lower($name).Str::lower(Str::random(8)),
            'pronouns' => fake()->randomElement(['iel', 'elle', 'il', 'ael']),
            'bio' => fake()->sentence(),
            'privacy_level' => PrivacyLevel::Public,
            'settings' => ['show_connections' => false],
        ];
    }

    public function private(): static
    {
        return $this->state(['privacy_level' => PrivacyLevel::Private]);
    }

    public function unlisted(): static
    {
        return $this->state(['privacy_level' => PrivacyLevel::Unlisted]);
    }

    public function readOnly(): static
    {
        return $this->state(['privacy_level' => PrivacyLevel::ReadOnly]);
    }
}
