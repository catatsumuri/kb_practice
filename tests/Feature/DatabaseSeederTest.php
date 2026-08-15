<?php

use App\Enums\DocumentVisibility;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('各テストユーザーに公開範囲を変えた10件のサンプルドキュメントを作成する', function () {
    $this->seed();

    $users = User::query()->with('documents')->orderBy('id')->get();

    expect($users)->toHaveCount(2)
        ->and(Document::query()->count())->toBe(20)
        ->and(Document::query()->distinct()->pluck('content'))->toHaveCount(1);

    foreach ($users as $user) {
        expect($user->documents)->toHaveCount(10)
            ->and($user->documents->pluck('visibility')->unique()->values()->all())->toEqualCanonicalizing(DocumentVisibility::cases())
            ->and($user->documents->pluck('title')->unique())->toHaveCount(10);

        foreach ($user->documents as $document) {
            $visibilityLabel = match ($document->visibility) {
                DocumentVisibility::Private => '非公開',
                DocumentVisibility::Public => '公開',
                DocumentVisibility::Unlisted => '限定公開',
            };

            expect($document->title)->toContain("（{$visibilityLabel}）");
        }
    }
});
