<?php

return [
    'institution_cache_ttl_seconds' => (int) env('REPORT_INSTITUTION_CACHE_TTL', 300),
    'pdf' => [
        'paper' => env('REPORT_PDF_PAPER', 'a4'),
        'orientation' => env('REPORT_PDF_ORIENTATION', 'portrait'),
        'dpi' => (int) env('REPORT_PDF_DPI', 96),
        'remote_enabled' => filter_var(env('REPORT_PDF_REMOTE_ENABLED', false), FILTER_VALIDATE_BOOL),
    ],
    'request_timeout_seconds' => (int) env('REPORT_REQUEST_TIMEOUT_SECONDS', 120),
    'preview_enabled' => env('REPORT_PREVIEW_ENABLED', false),
];
