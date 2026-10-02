<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Maximum Dimension
    |--------------------------------------------------------------------------
    |
    | Uploaded images whose width or height exceeds this many pixels are
    | scaled down (keeping the aspect ratio) before being stored.
    |
    */

    'max_dimension' => (int) env('IMAGE_MAX_DIMENSION', 2048),

    /*
    |--------------------------------------------------------------------------
    | Signed URL Lifetime
    |--------------------------------------------------------------------------
    |
    | Minutes an image URL stays valid once it has been signed for display.
    |
    */

    'signed_url_minutes' => (int) env('IMAGE_SIGNED_URL_MINUTES', 60),

];
