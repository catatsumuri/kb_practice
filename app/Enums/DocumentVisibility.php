<?php

namespace App\Enums;

enum DocumentVisibility: string
{
    case Private = 'private';
    case Public = 'public';
    case Unlisted = 'unlisted';
}
