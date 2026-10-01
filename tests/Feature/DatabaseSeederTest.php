<?php

use App\Actions\BackupNamespace;
use App\Models\Document;
use App\Models\DocumentNamespace;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\File;

function typesafeBackupPath(): string
{
    return storage_path(DatabaseSeeder::TYPESAFE_BACKUP);
}

beforeEach(function () {
    $this->storagePath = sys_get_temp_dir().'/seeder-storage-'.uniqid();
    $this->app->useStoragePath($this->storagePath);
});

afterEach(function () {
    File::deleteDirectory($this->storagePath);
});

test('the seeder creates two test users and no namespace when no typesafe backup is present', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::orderBy('id')->pluck('email')->all())->toBe(['test@example.com', 'test2@example.com'])
        ->and(DocumentNamespace::count())->toBe(0);
});

test('the seeder restores the typesafe namespace from its backup, owned by the first test user', function () {
    $source = DocumentNamespace::factory()->create([
        'slug' => 'typesafe',
        'name' => 'TypeSafe AI Docs',
        'navigation' => [['title' => 'Start', 'pages' => ['introduction']]],
    ]);
    Document::factory()->for($source->owner)->create([
        'document_namespace_id' => $source->id,
        'path' => 'introduction',
        'title' => 'はじめに',
    ]);
    File::ensureDirectoryExists(dirname(typesafeBackupPath()));
    app(BackupNamespace::class)($source, typesafeBackupPath());
    $source->owner->delete();

    $this->seed(DatabaseSeeder::class);

    $namespace = DocumentNamespace::where('slug', 'typesafe')->firstOrFail();

    expect($namespace->owner->email)->toBe('test@example.com')
        ->and($namespace->navigation)->toBe([['title' => 'Start', 'pages' => ['introduction']]])
        ->and($namespace->documents()->pluck('title', 'path')->all())->toBe(['introduction' => 'はじめに']);
});
