<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('未認証ユーザーはダッシュボードからログイン画面へリダイレクトされる', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('認証済みユーザーはダッシュボードを表示できる', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});
