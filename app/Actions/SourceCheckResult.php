<?php

namespace App\Actions;

use App\Enums\SourceCheckStatus;
use App\Models\Document;

readonly class SourceCheckResult
{
    public function __construct(
        public Document $document,
        public SourceCheckStatus $status,
        public ?string $message = null,
    ) {}
}
