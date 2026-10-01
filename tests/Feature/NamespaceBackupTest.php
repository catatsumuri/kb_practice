<?php

use App\Actions\BackupNamespace;
use App\Enums\DocumentType;
use App\Enums\DocumentVisibility;
use App\Models\Document;
use App\Models\DocumentNamespace;
use App\Models\DocumentRevision;
use App\Models\DocumentSourceSnapshot;
use App\Models\User;

function backupZipPath(): string
{
    return sys_get_temp_dir().'/namespace-backup-'.uniqid().'.zip';
}

function zipEntries(string $path): array
{
    $zip = new ZipArchive;
    $zip->open($path);
    $names = array_map(fn (int $index) => $zip->getNameIndex($index), range(0, $zip->numFiles - 1));
    $zip->close();
    sort($names);

    return $names;
}

function typesafeNamespaceWithDocuments(): DocumentNamespace
{
    $namespace = DocumentNamespace::factory()->create([
        'slug' => 'typesafe',
        'name' => 'TypeSafe AI Docs',
        'source_url' => 'https://docs.typesafe.ai',
        'is_public' => true,
        'navigation' => [['title' => 'Intro', 'pages' => ['introduction', 'sdk/python']]],
    ]);

    Document::factory()->for($namespace->owner)->create([
        'document_namespace_id' => $namespace->id,
        'path' => 'introduction',
        'title' => 'はじめに',
        'content' => "# はじめに\n\n---\n\n本文",
        'visibility' => DocumentVisibility::Public,
        'document_type' => DocumentType::Translation,
        'source_content' => "# Introduction\n\nbody",
        'source_url' => 'https://docs.typesafe.ai/introduction',
    ]);

    Document::factory()->for($namespace->owner)->create([
        'document_namespace_id' => $namespace->id,
        'path' => 'sdk/python',
        'title' => 'Python SDK',
        'visibility' => DocumentVisibility::Private,
    ]);

    return $namespace;
}

test('the backup archive holds each document under its nested path and omits snapshots and revisions by default', function () {
    $namespace = typesafeNamespaceWithDocuments();
    $zipPath = backupZipPath();

    $count = app(BackupNamespace::class)($namespace, $zipPath);

    expect($count)->toBe(2)
        ->and(zipEntries($zipPath))->toBe([
            '_backup.json',
            '_namespace.json',
            'content/introduction.md',
            'content/sdk/python.md',
            'meta/introduction.json',
            'meta/sdk/python.json',
            'source/introduction.md',
        ]);

    $zip = new ZipArchive;
    $zip->open($zipPath);

    expect(json_decode($zip->getFromName('meta/introduction.json'), true))->not->toHaveKeys(['snapshots', 'revisions']);

    unlink($zipPath);
});

test('the backup command writes a zip for the namespace', function () {
    typesafeNamespaceWithDocuments();
    $zipPath = backupZipPath();

    $this->artisan('namespace:backup', ['namespace' => 'typesafe', '--output' => $zipPath])
        ->assertSuccessful();

    expect($zipPath)->toBeFile();

    unlink($zipPath);
});

test('the backup command fails for an unknown namespace', function () {
    $this->artisan('namespace:backup', ['namespace' => 'missing'])
        ->expectsOutputToContain('Namespace not found.')
        ->assertFailed();
});

test('a backup restores into an empty database with every field intact', function () {
    $namespace = typesafeNamespaceWithDocuments();
    $document = $namespace->documents()->where('path', 'introduction')->first();
    $adopted = DocumentSourceSnapshot::factory()->for($document)->create(['content' => 'adopted']);
    DocumentSourceSnapshot::factory()->for($document)->create(['content' => 'newer']);
    $document->update(['document_source_snapshot_id' => $adopted->id]);
    DocumentRevision::factory()->for($document)->create(['title' => '旧題', 'content' => '旧本文']);

    $zipPath = backupZipPath();
    app(BackupNamespace::class)($namespace, $zipPath, withSnapshots: true, withRevisions: true);

    $namespace->owner->delete();
    $newOwner = User::factory()->create();

    $this->artisan('namespace:restore', ['path' => $zipPath, '--user' => $newOwner->email])
        ->assertSuccessful();

    $restored = DocumentNamespace::where('slug', 'typesafe')->firstOrFail();
    $introduction = $restored->documents()->where('path', 'introduction')->firstOrFail();

    expect($restored->owner_user_id)->toBe($newOwner->id)
        ->and($restored->name)->toBe('TypeSafe AI Docs')
        ->and($restored->source_url)->toBe('https://docs.typesafe.ai')
        ->and($restored->is_public)->toBeTrue()
        ->and($restored->navigation)->toBe([['title' => 'Intro', 'pages' => ['introduction', 'sdk/python']]])
        ->and($restored->documents)->toHaveCount(2)
        ->and($introduction->title)->toBe('はじめに')
        ->and($introduction->content)->toBe("# はじめに\n\n---\n\n本文")
        ->and($introduction->source_content)->toBe("# Introduction\n\nbody")
        ->and($introduction->visibility)->toBe(DocumentVisibility::Public)
        ->and($introduction->document_type)->toBe(DocumentType::Translation)
        ->and($introduction->source_url)->toBe('https://docs.typesafe.ai/introduction')
        ->and($introduction->sourceSnapshots->pluck('content')->all())->toBe(['adopted', 'newer'])
        ->and($introduction->adoptedSourceSnapshot->content)->toBe('adopted')
        ->and($introduction->revisions->pluck('title')->all())->toBe(['旧題'])
        ->and($restored->documents()->where('path', 'sdk/python')->first()->visibility)->toBe(DocumentVisibility::Private);

    unlink($zipPath);
});

test('restoring over an existing namespace overwrites matching documents and keeps the others', function () {
    $namespace = typesafeNamespaceWithDocuments();
    $zipPath = backupZipPath();
    app(BackupNamespace::class)($namespace, $zipPath);

    $namespace->documents()->where('path', 'introduction')->update(['content' => '編集後']);
    $extra = Document::factory()->for($namespace->owner)->create([
        'document_namespace_id' => $namespace->id,
        'path' => 'added-later',
    ]);

    $this->artisan('namespace:restore', ['path' => $zipPath])->assertSuccessful();

    expect($namespace->documents()->where('path', 'introduction')->first()->content)->toBe("# はじめに\n\n---\n\n本文")
        ->and($extra->fresh())->not->toBeNull()
        ->and($namespace->documents()->count())->toBe(3);

    unlink($zipPath);
});

test('restoring leaves existing snapshots untouched when the backup did not include them', function () {
    $namespace = typesafeNamespaceWithDocuments();
    $document = $namespace->documents()->where('path', 'introduction')->first();
    DocumentSourceSnapshot::factory()->for($document)->create();
    $zipPath = backupZipPath();
    app(BackupNamespace::class)($namespace, $zipPath);

    $this->artisan('namespace:restore', ['path' => $zipPath])->assertSuccessful();

    expect($document->sourceSnapshots()->count())->toBe(1);

    unlink($zipPath);
});

test('restoring rejects a namespace owned by a different user and changes nothing', function () {
    $namespace = typesafeNamespaceWithDocuments();
    $zipPath = backupZipPath();
    app(BackupNamespace::class)($namespace, $zipPath);
    $namespace->documents()->where('path', 'introduction')->update(['content' => '編集後']);

    $this->artisan('namespace:restore', ['path' => $zipPath, '--user' => User::factory()->create()->id])
        ->expectsOutputToContain('belongs to a different user')
        ->assertFailed();

    expect($namespace->documents()->where('path', 'introduction')->first()->content)->toBe('編集後');

    unlink($zipPath);
});

test('restoring rejects an archive with a path traversal entry', function () {
    $zipPath = backupZipPath();
    $zip = new ZipArchive;
    $zip->open($zipPath, ZipArchive::CREATE);
    $zip->addFromString('_backup.json', json_encode(['format_version' => 1]));
    $zip->addFromString('_namespace.json', json_encode(['slug' => 'evil', 'name' => 'Evil']));
    $zip->addFromString('content/../../escape.md', 'x');
    $zip->close();
    User::factory()->create();

    $this->artisan('namespace:restore', ['path' => $zipPath])
        ->expectsOutputToContain('invalid file path')
        ->assertFailed();

    expect(DocumentNamespace::where('slug', 'evil')->exists())->toBeFalse();

    unlink($zipPath);
});

test('restoring rejects an unsupported format version', function () {
    $zipPath = backupZipPath();
    $zip = new ZipArchive;
    $zip->open($zipPath, ZipArchive::CREATE);
    $zip->addFromString('_backup.json', json_encode(['format_version' => 99]));
    $zip->addFromString('_namespace.json', json_encode(['slug' => 'future', 'name' => 'Future']));
    $zip->close();
    User::factory()->create();

    $this->artisan('namespace:restore', ['path' => $zipPath])
        ->expectsOutputToContain('Unsupported backup format version.')
        ->assertFailed();

    unlink($zipPath);
});

test('restoring rejects a reserved slug for a new namespace', function () {
    $zipPath = backupZipPath();
    $zip = new ZipArchive;
    $zip->open($zipPath, ZipArchive::CREATE);
    $zip->addFromString('_backup.json', json_encode(['format_version' => 1]));
    $zip->addFromString('_namespace.json', json_encode(['slug' => 'dashboard', 'name' => 'D']));
    $zip->close();
    User::factory()->create();

    $this->artisan('namespace:restore', ['path' => $zipPath])
        ->expectsOutputToContain('is reserved')
        ->assertFailed();

    unlink($zipPath);
});
