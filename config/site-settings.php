<?php

return [
    'definitions' => [
        'site_name' => [
            'type' => 'string',
            'group' => 'general',
            'default' => env('APP_NAME', 'BebasInfo'),
            'public' => true,
        ],
        'site_tagline' => [
            'type' => 'string',
            'group' => 'general',
            'default' => 'Informasi bebas, untuk semua.',
            'public' => true,
        ],
        'site_description' => [
            'type' => 'text',
            'group' => 'general',
            'default' => 'BebasInfo menyajikan berita terkini seputar nasional, ekonomi, teknologi, olahraga, dan gaya hidup dengan bahasa jelas dan mudah dipahami.',
            'public' => true,
        ],
        'site_logo' => [
            'type' => 'image',
            'group' => 'general',
            'default' => null,
            'public' => true,
        ],
        'site_favicon' => [
            'type' => 'image',
            'group' => 'general',
            'default' => null,
            'public' => true,
        ],
        'contact_email' => [
            'type' => 'email',
            'group' => 'contact',
            'default' => null,
            'public' => true,
        ],
        'contact_phone' => [
            'type' => 'string',
            'group' => 'contact',
            'default' => null,
            'public' => true,
        ],
        'contact_address' => [
            'type' => 'text',
            'group' => 'contact',
            'default' => null,
            'public' => true,
        ],
        'default_seo_title' => [
            'type' => 'string',
            'group' => 'seo',
            'default' => 'BebasInfo - Informasi Bebas untuk Semua',
            'public' => true,
        ],
        'default_seo_description' => [
            'type' => 'text',
            'group' => 'seo',
            'default' => 'BebasInfo menyajikan berita terbaru, akurat, dan mudah dipahami tentang nasional, ekonomi, teknologi, olahraga, dan gaya hidup.',
            'public' => true,
        ],
        'default_og_image' => [
            'type' => 'image',
            'group' => 'seo',
            'default' => null,
            'public' => true,
        ],
        'default_robots_index' => [
            'type' => 'boolean',
            'group' => 'seo',
            'default' => true,
            'public' => true,
        ],
        'default_robots_follow' => [
            'type' => 'boolean',
            'group' => 'seo',
            'default' => true,
            'public' => true,
        ],
        'facebook_url' => [
            'type' => 'url',
            'group' => 'social',
            'default' => null,
            'public' => true,
        ],
        'instagram_url' => [
            'type' => 'url',
            'group' => 'social',
            'default' => null,
            'public' => true,
        ],
        'youtube_url' => [
            'type' => 'url',
            'group' => 'social',
            'default' => null,
            'public' => true,
        ],
        'x_url' => [
            'type' => 'url',
            'group' => 'social',
            'default' => null,
            'public' => true,
        ],
        'tiktok_url' => [
            'type' => 'url',
            'group' => 'social',
            'default' => null,
            'public' => true,
        ],
        'footer_text' => [
            'type' => 'text',
            'group' => 'footer',
            'default' => 'BebasInfo adalah media informasi digital dengan prinsip jelas, bebas, dan mudah diakses semua pembaca.',
            'public' => true,
        ],
    ],
];
