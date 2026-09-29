<?php

use App\Models\DocumentNamespace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

afterEach(function () {
    File::delete(database_path('seeders/testns-sample-source.md'));
});

test('the command saves the fetched page with anchors under database/seeders', function () {
    DocumentNamespace::factory()->create(['slug' => 'testns', 'source_url' => 'https://docs.example.test']);
    Http::fake(['docs.example.test/section/sample.md' => Http::response("# Sample\n\nBody\n")]);

    $this->artisan('documents:import-source', ['path' => 'section/sample', '--namespace' => 'testns'])
        ->assertSuccessful();

    expect(File::get(database_path('seeders/testns-sample-source.md')))
        ->toBe("# Sample {#sample}\n\nBody\n");
});

test('the command fails for a namespace without a source_url', function () {
    DocumentNamespace::factory()->create(['slug' => 'testns', 'source_url' => null]);
    Http::fake();

    $this->artisan('documents:import-source', ['path' => 'sample', '--namespace' => 'testns'])
        ->assertFailed();

    Http::assertNothingSent();
});

test('the command fails when the page cannot be fetched', function () {
    DocumentNamespace::factory()->create(['slug' => 'testns', 'source_url' => 'https://docs.example.test']);
    Http::fake(['*' => Http::response('nope', 404)]);

    $this->artisan('documents:import-source', ['path' => 'sample', '--namespace' => 'testns'])
        ->assertFailed();

    expect(File::exists(database_path('seeders/testns-sample-source.md')))->toBeFalse();
});
