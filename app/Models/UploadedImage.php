<?php

namespace App\Models;

use Database\Factories\UploadedImageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['path', 'disk', 'exif_data'])]
class UploadedImage extends Model
{
    /** @use HasFactory<UploadedImageFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'exif_data' => 'array',
        ];
    }
}
