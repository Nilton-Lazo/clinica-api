<?php

return [
    'institution_cache_ttl_seconds' => (int) env('REPORT_INSTITUTION_CACHE_TTL', 300),
    'pdf_driver' => env('REPORT_PDF_DRIVER', 'dompdf'),
    'pdf' => [
        'paper' => env('REPORT_PDF_PAPER', 'a4'),
        'orientation' => env('REPORT_PDF_ORIENTATION', 'portrait'),
        'dpi' => (int) env('REPORT_PDF_DPI', 96),
        'remote_enabled' => filter_var(env('REPORT_PDF_REMOTE_ENABLED', false), FILTER_VALIDATE_BOOL),
    ],
    'browsershot' => [
        'no_sandbox' => filter_var(env('BROWSERSHOT_NO_SANDBOX', true), FILTER_VALIDATE_BOOL),
        'node_binary' => env('BROWSERSHOT_NODE_BINARY'),
        'npm_binary' => env('BROWSERSHOT_NPM_BINARY'),
        'chrome_path' => env('BROWSERSHOT_CHROME_PATH'),
    ],
    'request_timeout_seconds' => (int) env('REPORT_REQUEST_TIMEOUT_SECONDS', 120),
    'preview_enabled' => filter_var(env('REPORT_PREVIEW_ENABLED', true), FILTER_VALIDATE_BOOL),
];
