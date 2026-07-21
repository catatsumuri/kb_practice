<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('ホーム画面を表示できる', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});
