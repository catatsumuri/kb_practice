<?php

use App\Actions\BackupNamespace;
use App\Actions\SignImageUrls;
use App\Enums\DocumentVisibility;
use App\Models\Document;
use App\Models\DocumentNamespace;
use App\Models\UploadedImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

function storeFakeImage(string $path = 'documents/sample.png'): string
{
    Storage::disk('public')->put($path, UploadedFile::fake()->image('sample.png', 40, 30)->getContent());

    return $path;
}

test('guests cannot upload images', function () {
    $this->postJson(route('documents.images.store'), [
        'image' => UploadedFile::fake()->image('a.png'),
    ])->assertUnauthorized();
});

test('an uploaded image is stored, recorded and returned as an unsigned url', function () {
    $response = $this->actingAs(User::factory()->create())
        ->postJson(route('documents.images.store'), [
            'image' => UploadedFile::fake()->image('a.png', 100, 80),
        ])
        ->assertCreated();

    $image = UploadedImage::firstOrFail();

    $response->assertExactJson(['url' => '/images/'.$image->path]);
    Storage::disk('public')->assertExists($image->path);
});

test('large images are scaled down to the maximum dimension', function () {
    config(['images.max_dimension' => 50]);

    $this->actingAs(User::factory()->create())
        ->postJson(route('documents.images.store'), [
            'image' => UploadedFile::fake()->image('big.png', 200, 100),
        ])
        ->assertCreated();

    $path = UploadedImage::firstOrFail()->path;
    [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($path));

    expect($width)->toBe(50)->and($height)->toBe(25);
});

test('gifs are stored without being re-encoded', function () {
    $file = UploadedFile::fake()->image('anim.gif', 20, 20);

    $this->actingAs(User::factory()->create())
        ->postJson(route('documents.images.store'), ['image' => $file])
        ->assertCreated();

    expect(Storage::disk('public')->get(UploadedImage::firstOrFail()->path))
        ->toBe(file_get_contents($file->getRealPath()));
});

test('non-images and oversized files are rejected', function (UploadedFile $file) {
    $this->actingAs(User::factory()->create())
        ->postJson(route('documents.images.store'), ['image' => $file])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('image');

    expect(UploadedImage::count())->toBe(0);
})->with([
    'text file' => fn () => UploadedFile::fake()->create('a.txt', 1, 'text/plain'),
    'over 5MB' => fn () => UploadedFile::fake()->image('big.png')->size(5121),
    'svg' => fn () => UploadedFile::fake()->create('a.svg', 1, 'image/svg+xml'),
]);

test('an image is served only with a valid signature', function () {
    $path = storeFakeImage();

    $this->get('/images/'.$path)->assertForbidden();

    $signed = URL::temporarySignedRoute('images.show', now()->addHour(), ['path' => $path], absolute: false);

    $this->get($signed)->assertSuccessful();
    $this->get($signed.'x')->assertForbidden();
});

test('an expired signature is rejected', function () {
    $path = storeFakeImage();
    $signed = URL::temporarySignedRoute('images.show', now()->addHour(), ['path' => $path], absolute: false);

    $this->travel(2)->hours();

    $this->get($signed)->assertForbidden();
});

test('a signed url for a missing image is not found', function () {
    $signed = URL::temporarySignedRoute('images.show', now()->addHour(), ['path' => 'documents/missing.png'], absolute: false);

    $this->get($signed)->assertNotFound();
});

test('the shown document has its image urls signed while the stored content is untouched', function () {
    $path = storeFakeImage();
    $document = Document::factory()->create([
        'visibility' => DocumentVisibility::Public,
        'content' => "![image](/images/{$path})\n\n![remote](https://example.com/images/other.png)",
    ]);

    $this->get(route('documents.show', $document))
        ->assertInertia(fn (Assert $page) => $page
            ->where('document.content', function (string $content) use ($path) {
                $signedUrl = (string) str($content)->after('![image](')->before(')');

                return str_contains($content, "/images/{$path}?expires=")
                    && str_contains($content, 'signature=')
                    && str_contains($content, '![remote](https://example.com/images/other.png)')
                    && $this->get($signedUrl)->isSuccessful();
            }));

    expect($document->fresh()->content)->toContain("![image](/images/{$path})");
});

test('the edit page receives the raw content', function () {
    $path = storeFakeImage();
    $document = Document::factory()->create(['content' => "![image](/images/{$path})"]);

    $this->actingAs($document->user)
        ->get(route('documents.edit', $document))
        ->assertInertia(fn (Assert $page) => $page
            ->where('document.content', "![image](/images/{$path})"));
});

test('image paths are extracted from markdown and traversal paths are ignored', function () {
    $paths = app(SignImageUrls::class)->paths(
        '![a](/images/documents/a.png) <img src="/images/documents/b.png"> ![a](/images/documents/a.png) ![x](/images/../.env)',
    );

    expect($paths)->toBe(['documents/a.png', 'documents/b.png']);
});

test('a backup carries referenced images and a restore writes them back', function () {
    $path = storeFakeImage();
    $namespace = DocumentNamespace::factory()->create(['slug' => 'pics']);
    Document::factory()->for($namespace->owner)->create([
        'document_namespace_id' => $namespace->id,
        'path' => 'intro',
        'content' => "![image](/images/{$path})",
    ]);
    Storage::disk('public')->put('documents/unused.png', 'x');

    $zipPath = sys_get_temp_dir().'/image-backup-'.uniqid().'.zip';
    app(BackupNamespace::class)($namespace, $zipPath);

    $zip = new ZipArchive;
    $zip->open($zipPath);
    expect($zip->locateName('images/'.$path))->not->toBeFalse()
        ->and($zip->locateName('images/documents/unused.png'))->toBeFalse();
    $zip->close();

    Storage::disk('public')->delete($path);
    $namespace->owner->documents()->delete();
    $namespace->delete();

    $this->artisan('namespace:restore', ['path' => $zipPath, '--user' => $namespace->owner->email])
        ->assertSuccessful();

    Storage::disk('public')->assertExists($path);
    expect(UploadedImage::where('path', $path)->exists())->toBeTrue();
});
