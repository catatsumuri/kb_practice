<?php

namespace App\Enums;

enum SourceCheckStatus: string
{
    case Unchanged = 'unchanged';
    case Updated = 'updated';
    case Pending = 'pending';
    case Failed = 'failed';
    case Skipped = 'skipped';
}
