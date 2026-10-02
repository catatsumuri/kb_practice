<?php

namespace Database\Factories;

use App\Models\UploadedImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UploadedImage>
 */
class UploadedImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'path' => 'documents/'.fake()->unique()->md5().'.jpg',
            'disk' => 'public',
            'exif_data' => null,
        ];
    }
}
