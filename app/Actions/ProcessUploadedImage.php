<?php

namespace App\Actions;

use App\Models\UploadedImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ProcessUploadedImage
{
    public const DIRECTORY = 'documents';

    /**
     * Store an uploaded image, scaling it down when it is larger than the
     * configured maximum dimension, and record it with its EXIF data.
     */
    public function __invoke(UploadedFile $file, string $directory = self::DIRECTORY, string $disk = 'public'): UploadedImage
    {
        $exifData = $this->readExif($file);
        $path = $this->processAndStore($file, $directory, $disk);

        return UploadedImage::create([
            'path' => $path,
            'disk' => $disk,
            'exif_data' => $exifData,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readExif(UploadedFile $file): ?array
    {
        if (! in_array($file->getMimeType(), ['image/jpeg', 'image/tiff'], true)) {
            return null;
        }

        $data = @exif_read_data($file->getRealPath(), null, true, false);

        if (! is_array($data)) {
            return null;
        }

        // Binary values (e.g. thumbnails, maker notes) are not valid UTF-8
        // and would make the JSON cast fail.
        $encoded = json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR);

        return is_string($encoded) ? json_decode($encoded, true) : null;
    }

    private function processAndStore(UploadedFile $file, string $directory, string $disk): string
    {
        $filename = $file->hashName();
        $path = "{$directory}/{$filename}";

        if ($file->getMimeType() === 'image/gif') {
            $file->storeAs($directory, $filename, $disk);

            return $path;
        }

        $maxDimension = (int) config('images.max_dimension');
        $image = (new ImageManager(new Driver))->read($file->getRealPath());

        if ($image->width() > $maxDimension || $image->height() > $maxDimension) {
            $image->scaleDown(width: $maxDimension, height: $maxDimension);
        }

        Storage::disk($disk)->put($path, (string) $image->encode());

        return $path;
    }
}
