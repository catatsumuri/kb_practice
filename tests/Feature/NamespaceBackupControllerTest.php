<?php

use App\Actions\BackupNamespace;
use App\Actions\ListNamespaceBackups;
use App\Enums\DocumentVisibility;
use App\Models\Document;
use App\Models\DocumentNamespace;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->originalStoragePath = storage_path();
    $this->backupStoragePath = sys_get_temp_dir().'/backup-web-tests-'.Str::uuid();
    app()->useStoragePath($this->backupStoragePath);
});

afterEach(function () {
    app()->useStoragePath($this->originalStoragePath);
    File::deleteDirectory($this->backupStoragePath);
});

test('所有者はZIPの説明と記事数を作成日時の降順で閲覧できる', function () {
    $namespace = DocumentNamespace::factory()->create();
    Document::factory()->for($namespace->owner)->create([
        'document_namespace_id' => $namespace->id,
        'path' => 'cookbooks/example',
    ]);
    $this->travelTo('2026-10-01 01:00:00');
    app(BackupNamespace::class)($namespace, BackupNamespace::directory().'/old.zip');
    $this->travelTo('2026-10-01 08:00:00');
    app(BackupNamespace::class)(
        $namespace,
        BackupNamespace::directory().'/new.zip',
        withSnapshots: true,
        withRevisions: true,
        description: '記事を1本翻訳しました',
    );
    $otherNamespace = DocumentNamespace::factory()->create();
    app(BackupNamespace::class)($otherNamespace, BackupNamespace::directory().'/other.zip');
    file_put_contents(BackupNamespace::directory().'/broken.zip', 'not a zip');

    $this->actingAs($namespace->owner)
        ->get(route('namespaces.backups.index', $namespace))
        ->assertInertia(fn (Assert $page) => $page
            ->component('namespaces/backups')
            ->where('namespace.slug', $namespace->slug)
            ->has('backups', 2)
            ->where('backups.0.filename', 'new.zip')
            ->where('backups.0.created_at', '2026-10-01T08:00:00+00:00')
            ->where('backups.0.description', '記事を1本翻訳しました')
            ->where('backups.0.document_count', 1)
            ->where('backups.0.with_snapshots', true)
            ->where('backups.0.with_revisions', true)
            ->where('backups.1.filename', 'old.zip')
            ->where('backups.1.description', null));
});

test('保存先がない場合も所有者には空の一覧を表示する', function () {
    $namespace = DocumentNamespace::factory()->create();

    $this->actingAs($namespace->owner)
        ->get(route('namespaces.backups.index', $namespace))
        ->assertInertia(fn (Assert $page) => $page
            ->component('namespaces/backups')
            ->has('backups', 0));
});

test('未認証ユーザーはバックアップ機能からログインに移動する', function (string $action) {
    $namespace = DocumentNamespace::factory()->create(['is_public' => true]);

    $this->get(route('namespaces.backups.'.$action, ['namespace' => $namespace, 'backup' => 'test.zip']))
        ->assertRedirect(route('login'));
})->with(['index', 'download']);

test('公開名前空間でも所有者以外はバックアップにアクセスできない', function (string $action) {
    $namespace = DocumentNamespace::factory()->create(['is_public' => true]);
    $otherUser = User::factory()->create();

    $this->actingAs($otherUser)
        ->get(route('namespaces.backups.'.$action, ['namespace' => $namespace, 'backup' => 'test.zip']))
        ->assertForbidden();
})->with(['index', 'download']);

test('所有者はアーカイブをZIPとしてダウンロードできる', function () {
    $namespace = DocumentNamespace::factory()->create();
    $path = BackupNamespace::directory().'/download.zip';
    app(BackupNamespace::class)($namespace, $path);

    $response = $this->actingAs($namespace->owner)
        ->get(route('namespaces.backups.download', ['namespace' => $namespace, 'backup' => 'download.zip']))
        ->assertDownload('download.zip')
        ->assertHeader('Content-Type', 'application/zip');

    expect($response->baseResponse->getFile()->getPathname())->toBe($path);
});

test('自分の名前空間のURLで別の名前空間のZIPは取得できない', function () {
    $namespace = DocumentNamespace::factory()->create();
    $otherNamespace = DocumentNamespace::factory()->create();
    app(BackupNamespace::class)($otherNamespace, BackupNamespace::directory().'/other.zip');

    $this->actingAs($namespace->owner)
        ->get(route('namespaces.backups.download', ['namespace' => $namespace, 'backup' => 'other.zip']))
        ->assertNotFound();
});

test('不明または不正なZIPはダウンロードできない', function (string $filename) {
    $namespace = DocumentNamespace::factory()->create();
    File::ensureDirectoryExists(BackupNamespace::directory());
    file_put_contents(BackupNamespace::directory().'/broken.zip', 'invalid');

    $this->actingAs($namespace->owner)
        ->get(route('namespaces.backups.download', ['namespace' => $namespace, 'backup' => $filename]))
        ->assertNotFound();
})->with(['missing.zip', 'broken.zip', '..\\outside.zip']);

test('バックアップのシンボリックリンクは一覧にもダウンロードにも公開しない', function () {
    $namespace = DocumentNamespace::factory()->create();
    app(BackupNamespace::class)($namespace, storage_path('outside.zip'));
    File::ensureDirectoryExists(BackupNamespace::directory());
    symlink(storage_path('outside.zip'), BackupNamespace::directory().'/linked.zip');

    $this->actingAs($namespace->owner)
        ->get(route('namespaces.backups.download', ['namespace' => $namespace, 'backup' => 'linked.zip']))
        ->assertNotFound();
});

test('所有者は説明付きで名前空間全体のフルバックアップを作成できる', function () {
    $namespace = DocumentNamespace::factory()->create();
    $document = Document::factory()->for($namespace->owner)->create([
        'document_namespace_id' => $namespace->id,
        'path' => 'cookbooks/example',
        'content' => '翻訳本文',
        'source_content' => 'Original source',
        'visibility' => DocumentVisibility::Private,
    ]);
    $document->revisions()->create(['title' => '過去のタイトル', 'content' => '過去の本文']);
    $document->sourceSnapshots()->create([
        'content' => 'Original source',
        'content_hash' => hash('sha256', 'Original source'),
        'fetched_at' => now(),
    ]);
    $other = DocumentNamespace::factory()->create();
    Document::factory()->for($other->owner)->create(['document_namespace_id' => $other->id, 'path' => 'other']);

    $this->actingAs($namespace->owner)
        ->post(route('namespaces.backups.store', $namespace), ['description' => '翻訳が1本増えました。'])
        ->assertRedirect(route('namespaces.backups.index', $namespace));

    $backups = app(ListNamespaceBackups::class)($namespace);
    expect($backups)->toHaveCount(1);
    expect($backups[0])->toMatchArray([
        'description' => '翻訳が1本増えました。',
        'document_count' => 1,
        'with_revisions' => true,
        'with_snapshots' => true,
    ]);
    $zip = new ZipArchive;
    $zip->open(BackupNamespace::directory().'/'.$backups[0]['filename']);
    $meta = json_decode($zip->getFromName('meta/cookbooks/example.json'), true);
    expect($zip->getFromName('content/cookbooks/example.md'))->toBe('翻訳本文')
        ->and($zip->getFromName('source/cookbooks/example.md'))->toBe('Original source')
        ->and($meta['revisions'][0]['content'])->toBe('過去の本文')
        ->and($meta['snapshots'][0]['content'])->toBe('Original source')
        ->and($zip->getFromName('content/other.md'))->toBeFalse();
    $zip->close();
});

test('未認証ユーザーはバックアップを作成できない', function () {
    $namespace = DocumentNamespace::factory()->create();

    $this->post(route('namespaces.backups.store', $namespace))->assertRedirect(route('login'));

    expect(glob(BackupNamespace::directory().'/*.zip') ?: [])->toBeEmpty();
});

test('他のユーザーは公開名前空間のバックアップも作成できない', function () {
    $namespace = DocumentNamespace::factory()->create(['is_public' => true]);

    $this->actingAs(User::factory()->create())
        ->post(route('namespaces.backups.store', $namespace))
        ->assertForbidden();

    expect(glob(BackupNamespace::directory().'/*.zip') ?: [])->toBeEmpty();
});

test('不正な説明ではZIPを作成しない', function (mixed $description, string $message) {
    $namespace = DocumentNamespace::factory()->create();

    $this->actingAs($namespace->owner)
        ->post(route('namespaces.backups.store', $namespace), ['description' => $description])
        ->assertSessionHasErrors(['description' => $message]);

    expect(glob(BackupNamespace::directory().'/*.zip') ?: [])->toBeEmpty();
})->with([
    '文字列以外' => [['invalid'], '説明は文字列で入力してください。'],
    '2000文字超' => [str_repeat('あ', 2001), '説明は2000文字以内で入力してください。'],
]);

test('同じ秒に説明なしで作成しても既存ZIPを上書きしない', function () {
    $namespace = DocumentNamespace::factory()->create();
    $this->freezeTime();
    $oldPath = BackupNamespace::defaultPath($namespace);
    app(BackupNamespace::class)($namespace, $oldPath, description: '保持する説明');

    $this->actingAs($namespace->owner)
        ->post(route('namespaces.backups.store', $namespace))
        ->assertRedirect(route('namespaces.backups.index', $namespace));

    expect(app(ListNamespaceBackups::class)($namespace))->toHaveCount(2);
    $zip = new ZipArchive;
    $zip->open($oldPath);
    expect(json_decode($zip->getFromName('_backup.json'), true)['description'])->toBe('保持する説明');
    $zip->close();
    expect(collect(app(ListNamespaceBackups::class)($namespace))->pluck('description')->all())->toContain(null);
});

test('所有者は指定したZIPだけを削除し記事と他のバックアップを保持する', function () {
    $namespace = DocumentNamespace::factory()->create();
    $document = Document::factory()->for($namespace->owner)->create([
        'document_namespace_id' => $namespace->id,
        'path' => 'intro',
    ]);
    $targetPath = BackupNamespace::directory().'/target.zip';
    $keepPath = BackupNamespace::directory().'/keep.zip';
    app(BackupNamespace::class)($namespace, $targetPath);
    app(BackupNamespace::class)($namespace, $keepPath);

    $this->actingAs($namespace->owner)
        ->delete(route('namespaces.backups.destroy', ['namespace' => $namespace, 'backup' => 'target.zip']))
        ->assertRedirect(route('namespaces.backups.index', $namespace));

    expect(is_file($targetPath))->toBeFalse()
        ->and(is_file($keepPath))->toBeTrue();
    $this->assertModelExists($document);
    $this->assertModelExists($namespace);
});

test('未認証ユーザーはZIPを削除できない', function () {
    $namespace = DocumentNamespace::factory()->create();
    $path = BackupNamespace::directory().'/keep.zip';
    app(BackupNamespace::class)($namespace, $path);

    $this->delete(route('namespaces.backups.destroy', ['namespace' => $namespace, 'backup' => 'keep.zip']))
        ->assertRedirect(route('login'));

    expect(is_file($path))->toBeTrue();
});

test('所有者以外は公開名前空間のZIPも削除できない', function () {
    $namespace = DocumentNamespace::factory()->create(['is_public' => true]);
    $path = BackupNamespace::directory().'/keep.zip';
    app(BackupNamespace::class)($namespace, $path);

    $this->actingAs(User::factory()->create())
        ->delete(route('namespaces.backups.destroy', ['namespace' => $namespace, 'backup' => 'keep.zip']))
        ->assertForbidden();

    expect(is_file($path))->toBeTrue();
});

test('別の名前空間のZIPは削除できない', function () {
    $namespace = DocumentNamespace::factory()->create();
    $other = DocumentNamespace::factory()->create();
    $path = BackupNamespace::directory().'/other.zip';
    app(BackupNamespace::class)($other, $path);

    $this->actingAs($namespace->owner)
        ->delete(route('namespaces.backups.destroy', ['namespace' => $namespace, 'backup' => 'other.zip']))
        ->assertNotFound();

    expect(is_file($path))->toBeTrue();
});

test('存在しないZIPやディレクトリ外への指定は削除できない', function (string $filename) {
    $namespace = DocumentNamespace::factory()->create();
    $path = storage_path('outside.zip');
    app(BackupNamespace::class)($namespace, $path);
    File::ensureDirectoryExists(BackupNamespace::directory());
    symlink($path, BackupNamespace::directory().'/linked.zip');

    $this->actingAs($namespace->owner)
        ->delete(route('namespaces.backups.destroy', ['namespace' => $namespace, 'backup' => $filename]))
        ->assertNotFound();

    expect(is_file($path))->toBeTrue()
        ->and(is_link(BackupNamespace::directory().'/linked.zip'))->toBeTrue();
})->with(['missing.zip', '..\\outside.zip', 'linked.zip']);

test('ファイル削除に失敗した場合はエラーを返してZIPを保持する', function () {
    $namespace = DocumentNamespace::factory()->create();
    $path = BackupNamespace::directory().'/keep.zip';
    app(BackupNamespace::class)($namespace, $path);
    File::partialMock()->shouldReceive('delete')->once()->with($path)->andReturn(false);

    $this->actingAs($namespace->owner)
        ->delete(route('namespaces.backups.destroy', ['namespace' => $namespace, 'backup' => 'keep.zip']))
        ->assertRedirect(route('namespaces.backups.index', $namespace))
        ->assertSessionHasErrors(['backup' => 'バックアップを削除できませんでした。もう一度お試しください。']);

    expect(is_file($path))->toBeTrue();
});
