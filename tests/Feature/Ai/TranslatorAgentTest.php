<?php

use App\Ai\Agents\TranslatorAgent;

test('it translates the given text into the configured app locale', function () {
    config(['app.locale' => 'ja']);

    TranslatorAgent::fake(['大規模言語モデル']);

    $response = (new TranslatorAgent)->prompt('Large language models');

    expect($response->text)->toBe('大規模言語モデル');
    TranslatorAgent::assertPrompted('Large language models');
});

test('its instructions target the app locale without being told the source language', function () {
    config(['app.locale' => 'fr']);

    $instructions = (string) (new TranslatorAgent)->instructions();

    expect($instructions)
        ->toContain('"fr"')
        ->toContain('Identify the language of the');
});

test('its instructions keep emphasis renderable when a closing marker follows punctuation', function () {
    $instructions = (string) (new TranslatorAgent)->instructions();

    expect($instructions)->toContain('half-width space after the closing marker');
});
