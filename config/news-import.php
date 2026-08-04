<?php

return [
    'enabled' => env('NEWS_IMPORT_ENABLED', true),
    'async' => env('NEWS_IMPORT_ASYNC', false),
    'max_zip_mb' => (int) env('NEWS_IMPORT_MAX_ZIP_MB', 50),
    'max_posts' => (int) env('NEWS_IMPORT_MAX_POSTS', 20),
    'max_files' => (int) env('NEWS_IMPORT_MAX_FILES', 100),
    'max_uncompressed_mb' => (int) env('NEWS_IMPORT_MAX_UNCOMPRESSED_MB', 200),
    'allow_publish' => env('NEWS_IMPORT_ALLOW_PUBLISH', true),
    'allow_default_image' => env('NEWS_IMPORT_ALLOW_DEFAULT_IMAGE', true),
    'default_image_path' => env('NEWS_IMPORT_DEFAULT_IMAGE_PATH', env('MEDIA_DEFAULT_FEATURED_IMAGE', '/images/default.png')),
    'token_expiry_days' => (int) env('NEWS_IMPORT_TOKEN_EXPIRY_DAYS', 90),
    'rate_limit' => (int) env('NEWS_IMPORT_RATE_LIMIT', 10),
    'log_retention_days' => (int) env('NEWS_IMPORT_LOG_RETENTION_DAYS', 90),
    'strict_mode' => env('NEWS_IMPORT_STRICT_MODE', false),
    'supported_manifest_versions' => ['1.0'],
    'allowed_package_files' => [
        'manifest.json',
        'posts.json',
        'sources.json',
    ],
    'allowed_image_extensions' => ['jpg', 'jpeg', 'png', 'webp'],
];
