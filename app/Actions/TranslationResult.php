<?php

namespace App\Actions;

readonly class TranslationResult
{
    public function __construct(
        public ?string $model,
        public int $inputTokens,
        public int $outputTokens,
        public int $chunkCount,
    ) {}
}
