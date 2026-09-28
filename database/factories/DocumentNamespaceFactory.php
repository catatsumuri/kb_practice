<?php

namespace Database\Factories;

use App\Models\DocumentNamespace;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentNamespace>
 */
class DocumentNamespaceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_user_id' => User::factory(),
            'slug' => fake()->unique()->slug(),
            'name' => fake()->company(),
        ];
    }
}
