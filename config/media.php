<?php

return [
    'disk' => env('MEDIA_DISK', 'public'),

    'featured' => [
        'directory' => 'posts/featured',
        'default_image' => env('MEDIA_DEFAULT_FEATURED_IMAGE', '/images/default.png'),
        'default_alt' => env('MEDIA_DEFAULT_FEATURED_ALT', 'Gambar berita'),
        'max_size' => 5120,
        'min_width' => 1200,
        'min_height' => 675,
        'quality' => 82,
        'sizes' => [
            'original' => [1600, 900],
            'large' => [1600, 900],
            'medium' => [960, 540],
            'thumbnail' => [480, 270],
        ],
    ],

    'content' => [
        'directory' => 'posts/content',
        'max_size' => 5120,
        'max_width' => 1600,
        'quality' => 82,
    ],

    'accepted_mime_types' => [
        'image/jpeg',
        'image/png',
        'image/webp',
    ],
];
