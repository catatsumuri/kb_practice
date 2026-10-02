<?php

namespace App\Http\Controllers;

use App\Actions\ProcessUploadedImage;
use App\Http\Requests\UploadImageRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImageController extends Controller
{
    /**
     * Store an uploaded image and return the unsigned URL to put in the
     * Markdown. It is signed when the document is displayed.
     */
    public function store(UploadImageRequest $request, ProcessUploadedImage $processUploadedImage): JsonResponse
    {
        $image = $processUploadedImage($request->file('image'));

        return response()->json(['url' => '/images/'.$image->path], 201);
    }

    /**
     * Stream a stored image. The route requires a valid signature.
     */
    public function show(string $path): StreamedResponse
    {
        abort_unless(Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path);
    }
}
