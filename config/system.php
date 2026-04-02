<?php

return [
    'branding' => [
        'organization' => env('SYSTEM_ORGANIZATION', 'UniKL'),
        'product_name' => env('SYSTEM_PRODUCT_NAME', 'STRG Request System'),
        'request_label' => env('SYSTEM_REQUEST_LABEL', 'Request'),
    ],

    'features' => [
        // Toggle the dean-specific UI routes without code edits.
        'dean_interface' => env('FEATURE_DEAN_INTERFACE', false),
    ],
];
