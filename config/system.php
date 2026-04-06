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

        // Enable advanced analytics and reporting features
        'advanced_analytics' => env('FEATURE_ADVANCED_ANALYTICS', true),

        // Enable email notifications for workflow events
        'email_notifications' => env('FEATURE_EMAIL_NOTIFICATIONS', true),

        // Enable strict file upload validation and virus scanning
        'strict_file_validation' => env('FEATURE_STRICT_FILE_VALIDATION', true),

        // Enable automatic priority calculation based on deadlines
        'auto_priority' => env('FEATURE_AUTO_PRIORITY', true),

        // Enable comprehensive audit logging for all system actions
        'audit_logging' => env('FEATURE_AUDIT_LOGGING', true),

        // Enable staff override capabilities for special circumstances
        'override_system' => env('FEATURE_OVERRIDE_SYSTEM', true),

        // Enable mobile-specific optimizations and responsive features
        'mobile_optimization' => env('FEATURE_MOBILE_OPTIMIZATION', true),

        // Enable performance monitoring and query logging
        'performance_monitoring' => env('FEATURE_PERFORMANCE_MONITORING', false),
    ],

    'settings' => [
        // Number of days before deadline to automatically set high priority
        'priority_threshold_days' => env('PRIORITY_THRESHOLD_DAYS', 3),

        // Maximum file size for uploads in megabytes
        'max_file_size_mb' => env('MAX_FILE_SIZE_MB', 10),

        // Session timeout in minutes
        'session_timeout_minutes' => env('SESSION_TIMEOUT_MINUTES', 120),

        // Default number of items per page for paginated results
        'pagination_limit' => env('PAGINATION_LIMIT', 25),

        // Maximum number of records that can be exported at once
        'export_limit' => env('EXPORT_LIMIT', 1000),

        // Default cache duration in minutes for frequently accessed data
        'cache_duration_minutes' => env('CACHE_DURATION_MINUTES', 60),

        // Enable maintenance mode for system updates
        'maintenance_mode' => env('MAINTENANCE_MODE', false),

        // Enable debug mode for development and troubleshooting
        'debug_mode' => env('APP_DEBUG', false),
    ],
];
