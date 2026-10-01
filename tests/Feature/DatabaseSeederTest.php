<?php

use App\Actions\BackupNamespace;
use App\Enums\DocumentVisibility;
use App\Models\Document;
use App\Models\DocumentNamespace;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->storagePath = sys_get_temp_dir().'/seeder-storage-'.uniqid();
    $this->app->useStoragePath($this->storagePath);
});

afterEach(function () {
    File::deleteDirectory($this->storagePath);
});

test('the seeder creates two test users and no namespace when no backups are present', function () {
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
        'visibility' => DocumentVisibility::Public,
    ]);
    app(BackupNamespace::class)($source, BackupNamespace::directory().'/typesafe.zip');
    $source->owner->delete();

    $this->seed(DatabaseSeeder::class);

    $namespace = DocumentNamespace::where('slug', 'typesafe')->firstOrFail();

    expect($namespace->owner->email)->toBe('test@example.com')
        ->and($namespace->guest_redirect_path)->toBe('introduction')
        ->and($namespace->navigation)->toBe([['title' => 'Start', 'pages' => ['introduction']]])
        ->and($namespace->documents()->pluck('title', 'path')->all())->toBe(['introduction' => 'はじめに']);
});

test('seed defaults do not overwrite a redirect saved in the backup', function () {
    $source = DocumentNamespace::factory()->create([
        'slug' => 'typesafe',
        'guest_redirect_path' => 'guide',
    ]);
    foreach (['guide', 'introduction'] as $path) {
        Document::factory()->for($source->owner)->create([
            'document_namespace_id' => $source->id,
            'path' => $path,
            'visibility' => DocumentVisibility::Public,
        ]);
    }
    app(BackupNamespace::class)($source, BackupNamespace::directory().'/typesafe.zip');
    $source->owner->delete();

    $this->seed(DatabaseSeeder::class);

    expect(DocumentNamespace::where('slug', 'typesafe')->sole()->guest_redirect_path)->toBe('guide');
});

test('seed defaults do not point at missing or private articles', function (bool $createPrivateDocument) {
    $source = DocumentNamespace::factory()->create(['slug' => 'typesafe']);
    if ($createPrivateDocument) {
        Document::factory()->for($source->owner)->create([
            'document_namespace_id' => $source->id,
            'path' => 'introduction',
            'visibility' => DocumentVisibility::Private,
        ]);
    }
    app(BackupNamespace::class)($source, BackupNamespace::directory().'/typesafe.zip');
    $source->owner->delete();

    $this->seed(DatabaseSeeder::class);

    expect(DocumentNamespace::where('slug', 'typesafe')->sole()->guest_redirect_path)->toBeNull();
})->with(['missing' => false, 'private' => true]);

test('the seeder restores only the latest archive for every namespace regardless of filename or modification time', function () {
    $owner = User::factory()->create();
    $first = DocumentNamespace::factory()->for($owner, 'owner')->create(['slug' => 'manual']);
    $document = Document::factory()->for($owner)->create([
        'document_namespace_id' => $first->id,
        'path' => 'guide',
        'title' => 'Old title',
        'content' => 'Old content',
    ]);
    $second = DocumentNamespace::factory()->for($owner, 'owner')->create(['slug' => 'notes']);
    Document::factory()->for($owner)->create([
        'document_namespace_id' => $second->id,
        'path' => 'intro',
        'title' => 'Notes introduction',
    ]);
    $this->travelTo('2026-10-01 01:00:00');
    app(BackupNamespace::class)($first, BackupNamespace::directory().'/zzz-older.zip');
    app(BackupNamespace::class)($second, BackupNamespace::directory().'/notes.zip');
    $document->update(['title' => 'Translated title', 'content' => 'Translated content']);
    $document->revisions()->create(['title' => 'Old title', 'content' => 'Old content']);
    $snapshot = $document->sourceSnapshots()->create([
        'content' => 'Original source',
        'content_hash' => hash('sha256', 'Original source'),
        'fetched_at' => now(),
    ]);
    $document->update(['source_content' => 'Original source', 'document_source_snapshot_id' => $snapshot->id]);
    $this->travelTo('2026-10-01 08:00:00');
    app(BackupNamespace::class)($first, BackupNamespace::directory().'/aaa-newer.zip', withSnapshots: true, withRevisions: true);
    touch(BackupNamespace::directory().'/zzz-older.zip', strtotime('2026-10-02'));
    $owner->delete();

    $this->seed(DatabaseSeeder::class);

    expect(DocumentNamespace::orderBy('slug')->pluck('slug')->all())->toBe(['manual', 'notes']);
    $restored = DocumentNamespace::where('slug', 'manual')->firstOrFail()->documents()->sole();
    expect($restored->title)->toBe('Translated title')
        ->and($restored->content)->toBe('Translated content')
        ->and($restored->user->email)->toBe('test@example.com')
        ->and($restored->revisions()->sole()->content)->toBe('Old content')
        ->and($restored->adoptedSourceSnapshot->content)->toBe('Original source');
    expect(DocumentNamespace::where('slug', 'notes')->firstOrFail()->documents()->sole()->title)->toBe('Notes introduction');
});

test('the seeder ignores broken archives and unsupported backup formats', function () {
    File::ensureDirectoryExists(BackupNamespace::directory());
    file_put_contents(BackupNamespace::directory().'/broken.zip', 'not a zip');
    $zip = new ZipArchive;
    $zip->open(BackupNamespace::directory().'/unsupported.zip', ZipArchive::CREATE);
    $zip->addFromString('_namespace.json', json_encode(['slug' => 'manual', 'name' => 'Manual']));
    $zip->addFromString('_backup.json', json_encode(['format_version' => 99]));
    $zip->close();

    $this->seed(DatabaseSeeder::class);

    expect(DocumentNamespace::count())->toBe(0)
        ->and(User::whereIn('email', ['test@example.com', 'test2@example.com'])->count())->toBe(2);
});
