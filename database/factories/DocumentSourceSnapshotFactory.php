<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentSourceSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentSourceSnapshot>
 */
class DocumentSourceSnapshotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $content = fake()->realText();

        return [
            'document_id' => Document::factory(),
            'content' => $content,
            'content_hash' => hash('sha256', $content),
            'title' => fake()->sentence(),
            'fetched_at' => fake()->dateTimeBetween('-1 month'),
        ];
    }
}
