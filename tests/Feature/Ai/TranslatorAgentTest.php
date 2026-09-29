<?php

use App\Ai\Agents\TranslatorAgent;
use Laravel\Ai\Attributes\Timeout;

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

test('its instructions carry the Japanese glossary and spacing rule', function () {
    $instructions = (string) (new TranslatorAgent)->instructions();

    expect($instructions)
        ->toContain('confidence = 確信度')
        ->toContain('flagship = フラッグシップ')
        ->toContain('Playground')
        ->toContain('do not put spaces between');
});

test('its instructions keep JSX tag attributes other than title untouched', function () {
    $instructions = (string) (new TranslatorAgent)->instructions();

    expect($instructions)
        ->toContain('translate only human-readable attribute')
        ->toContain('(icon, cols, href, ...)');
});

test('its instructions translate example prose in code blocks but not keys or identifiers', function () {
    $instructions = (string) (new TranslatorAgent)->instructions();

    expect($instructions)
        ->toContain('translate the')
        ->toContain('natural-language string values')
        ->toContain('Never')
        ->toContain('translate keys, identifiers')
        ->toContain('exactly the translation the request');
});

test('it allows long articles more than the default 60 second request timeout', function () {
    $attributes = (new ReflectionClass(TranslatorAgent::class))->getAttributes(Timeout::class);

    expect($attributes)->toHaveCount(1)
        ->and($attributes[0]->newInstance()->value)->toBeGreaterThan(60);
});
