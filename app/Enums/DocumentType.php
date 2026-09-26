<?php

namespace App\Enums;

enum DocumentType: string
{
    case Original = 'original';
    case Translation = 'translation';
}
