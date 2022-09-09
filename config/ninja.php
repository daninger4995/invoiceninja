<?php

return [

    'web_url' => 'https://www.invoiceninja.com',
    'admin_token' => env('NINJA_ADMIN_TOKEN', ''),
    'license_url' => 'https://app.invoiceninja.com',
    'production' => env('NINJA_PROD', false),
    'license'   => env('NINJA_LICENSE', ''),
    'version_url' => 'https://pdf.invoicing.co/api/version',
    'app_name' => env('APP_NAME', 'Invoice Ninja'),
    'app_env' => env('APP_ENV', 'selfhosted'),
    'debug_enabled' => env('APP_DEBUG', false),
    'require_https' => env('REQUIRE_HTTPS', true),
    'app_url' => rtrim(env('APP_URL', ''), '/'),
    'app_domain' => env('APP_DOMAIN', 'invoicing.co'),
    'app_version' => '5.5.21',
    'app_tag' => '5.5.21',
    'minimum_client_version' => '5.0.16',
    'terms_version' => '1.0.1',
    'api_secret' => env('API_SECRET', ''),
    'google_maps_api_key' => env('GOOGLE_MAPS_API_KEY'),
    'google_analytics_url' => env('GOOGLE_ANALYTICS_URL', 'https://www.google-analytics.com/collect'),
    'key_length' => 32,
    'date_format' => 'Y-m-d',
    'date_time_format' => 'Y-m-d H:i',
    'daily_email_limit' => 300,
    'error_email' => env('ERROR_EMAIL', ''),
    'mailer' => env('MAIL_MAILER', ''),
    'company_id' => 0,
    'hash_salt' => env('HASH_SALT', ''),
    'currency_converter_api_key' => env('OPENEXCHANGE_APP_ID', ''),
    'enabled_modules' => 32767,
    'phantomjs_key' => env('PHANTOMJS_KEY', 'a-demo-key-with-low-quota-per-ip-address'),
    'phantomjs_secret' => env('PHANTOMJS_SECRET', false),
    'phantomjs_pdf_generation' => env('PHANTOMJS_PDF_GENERATION', false),
    'pdf_generator' => env('PDF_GENERATOR', false),
    'trusted_proxies' => env('TRUSTED_PROXIES', false),
    'is_docker' => env('IS_DOCKER', false),
    'local_download' => env('LOCAL_DOWNLOAD', false),
    'sentry_dsn' => env('SENTRY_LARAVEL_DSN', 'https://39389664f3f14969b4c43dadda00a40b@sentry2.invoicing.co/5'),
    'environment' => env('NINJA_ENVIRONMENT', 'selfhost'), // 'hosted', 'development', 'selfhost', 'reseller'
    'preconfigured_install' => env('PRECONFIGURED_INSTALL', false),
    'update_secret' => env('UPDATE_SECRET', ''),
    // Settings used by invoiceninja.com

    'terms_of_service_url' => [
        'hosted' => env('TERMS_OF_SERVICE_URL', 'https://www.invoiceninja.com/terms/'),
        'selfhost' => env('TERMS_OF_SERVICE_URL', 'https://www.invoiceninja.com/self-hosting-terms-service/'),
    ],

    'privacy_policy_url' => [
        'hosted' => env('PRIVACY_POLICY_URL', 'https://www.invoiceninja.com/privacy-policy/'),
        'selfhost' => env('PRIVACY_POLICY_URL', 'https://www.invoiceninja.com/self-hosting-privacy-data-control/'),
    ],

    'db' => [
        'multi_db_enabled' => env('MULTI_DB_ENABLED', false),
        'default' => env('DB_CONNECTION', 'mysql'),
    ],

    'i18n' => [
        'timezone_id' => env('DEFAULT_TIMEZONE', 1),
        'country_id' => env('DEFAULT_COUNTRY', 840), // United Stated
        'currency_id' => env('DEFAULT_CURRENCY', 1),
        'language_id' => env('DEFAULT_LANGUAGE', 1), //en
        'date_format_id' => env('DEFAULT_DATE_FORMAT_ID', '1'),
        'datetime_format_id' => env('DEFAULT_DATETIME_FORMAT_ID', '1'),
        'locale' => env('DEFAULT_LOCALE', 'en'),
        'map_zoom' => env('DEFAULT_MAP_ZOOM', 10),
        'payment_terms' => env('DEFAULT_PAYMENT_TERMS', ''),
        'military_time' => env('MILITARY_TIME', 0),
        'first_day_of_week' => env('FIRST_DATE_OF_WEEK', 0),
        'first_month_of_year' => env('FIRST_MONTH_OF_YEAR', '2000-01-01'),
    ],

    'testvars' => [
        'username' => 'user@example.com',
        'clientname' => 'client@example.com',
        'password' => 'password',
        'stripe' => env('STRIPE_KEYS', ''),
        'paypal' => env('PAYPAL_KEYS', ''),
        'authorize' => env('AUTHORIZE_KEYS', ''),
        'checkout' => env('CHECKOUT_KEYS', ''),
        'travis' => env('TRAVIS', false),
        'test_email' => env('TEST_EMAIL', 'test@example.com'),
        'wepay' => env('WEPAY_KEYS', ''),
        'braintree' => env('BRAINTREE_KEYS', ''),
        'paytrace' => [
            'username' => env('PAYTRACE_U', ''),
            'password' => env('PAYTRACE_P', ''),
            'decrypted' => env('PAYTRACE_KEYS', ''),
        ],
        'mollie' => env('MOLLIE_KEYS', ''),
        'square' => env('SQUARE_KEYS', ''),
    ],
    'contact' => [
        'email' => env('MAIL_FROM_ADDRESS'),
        'from_name' => env('MAIL_FROM_NAME'),
        'ninja_official_contact' => env('NINJA_OFFICIAL_CONTACT', 'contact@invoiceninja.com'),
    ],
    'cached_tables' => [
        'banks' => App\Models\Bank::class,
        'countries' => App\Models\Country::class,
        'currencies' => App\Models\Currency::class,
        'date_formats' => App\Models\DateFormat::class,
        'datetime_formats' => App\Models\DatetimeFormat::class,
        'gateways' => App\Models\Gateway::class,
        //'gateway_types' => App\Models\GatewayType::class,
        'industries' => App\Models\Industry::class,
        'languages' => App\Models\Language::class,
        'payment_types' => App\Models\PaymentType::class,
        'sizes' => App\Models\Size::class,
        'timezones' => App\Models\Timezone::class,
        //'invoiceDesigns' => 'App\Models\InvoiceDesign',
        //'invoiceStatus' => 'App\Models\InvoiceStatus',
        //'frequencies' => 'App\Models\Frequency',
        //'fonts' => 'App\Models\Font',
    ],
    'notification' => [
        'slack' => env('SLACK_WEBHOOK_URL', false),
        'mail' => env('HOSTED_EMAIL', ''),
    ],
    'themes' => [
        'global' => 'ninja2020',
        'portal' => 'ninja2020',
    ],
    'quotas' => [
        'free' => [
            'daily_emails' => 50,
            'clients' => 20,
            'max_companies' => 1,
        ],
        'pro' => [
            'daily_emails' => 100,
            'clients' => 1000000,
            'max_companies' => 10,
        ],
        'enterprise' => [
            'daily_emails' => 200,
            'clients' => 1000000,
            'max_companies' => 10,
        ],
    ],
    'auth' => [
        'google' => [
            'client_id' => env('GOOGLE_CLIENT_ID', ''),
            'client_secret' => env('GOOGLE_CLIENT_SECRET', ''),
        ],
    ],
    'system' => [
        'node_path' => env('NODE_PATH', false),
        'npm_path' => env('NPM_PATH', false),
    ],
    'designs' => [
        'base_path' => resource_path('views/pdf-designs/'),
    ],
    'o365' => [
        'client_secret' => env('MICROSOFT_CLIENT_SECRET', false),
        'client_id' => env('MICROSOFT_CLIENT_ID', false),
        'tenant_id' => env('MICROSOFT_TENANT_ID', false),
    ],
    'maintenance' => [
        'delete_pdfs' => env('DELETE_PDF_DAYS', 0),
        'delete_backups' => env('DELETE_BACKUP_DAYS', 0),
    ],
    'log_pdf_html' => env('LOG_PDF_HTML', false),
    'expanded_logging' => env('EXPANDED_LOGGING', false),
    'snappdf_chromium_path' => env('SNAPPDF_CHROMIUM_PATH', false),
    'snappdf_chromium_arguments' => env('SNAPPDF_CHROMIUM_ARGUMENTS', false),
    'v4_migration_version' => '4.5.35',
    'flutter_renderer' => env('FLUTTER_RENDERER', 'selfhosted-html'),
    'webcron_secret' => env('WEBCRON_SECRET', false),
    'disable_auto_update' => env('DISABLE_AUTO_UPDATE', false),
    'invoiceninja_hosted_pdf_generation' => env('NINJA_HOSTED_PDF', false),
    'ninja_stripe_key' => env('NINJA_STRIPE_KEY', null),
    'wepay' => [
        'environment' => env('WEPAY_ENVIRONMENT', 'stage'),
        'client_id' => env('WEPAY_CLIENT_ID', ''),
        'client_secret' => env('WEPAY_CLIENT_SECRET', ''),
        'fee_payer' => env('WEPAY_FEE_PAYER'),
        'fee_cc_multiplier' => env('WEPAY_APP_FEE_CC_MULTIPLIER'),
        'fee_ach_multiplier' => env('WEPAY_APP_FEE_ACH_MULTIPLIER'),
        'fee_fixed' => env('WEPAY_APP_FEE_FIXED'),
    ],
    'ninja_stripe_publishable_key' => env('NINJA_PUBLISHABLE_KEY', null),
    'ninja_stripe_client_id' => env('NINJA_STRIPE_CLIENT_ID', null),
    'ninja_default_company_id' => env('NINJA_COMPANY_ID', null),
    'ninja_default_company_gateway_id' => env('NINJA_COMPANY_GATEWAY_ID', null),
    'ninja_hosted_secret' => env('NINJA_HOSTED_SECRET', null),
    'internal_queue_enabled' => env('INTERNAL_QUEUE_ENABLED', true),
    'ninja_apple_api_key' => env('APPLE_API_KEY', false),
    'ninja_apple_private_key' => env('APPLE_PRIVATE_KEY', false),
    'ninja_apple_bundle_id' => env('APPLE_BUNDLE_ID', false),
    'ninja_apple_issuer_id' => env('APPLE_ISSUER_ID', false),
    'react_app_enabled' => env('REACT_APP_ENABLED', false),
    'ninja_apple_client_id' => env('APPLE_CLIENT_ID', false),
    'ninja_apple_client_secret' => env('APPLE_CLIENT_SECRET',false),
    'ninja_apple_redirect_url' => env('APPLE_REDIRECT_URI',false),
    'twilio_account_sid' => env('TWILIO_ACCOUNT_SID',false),
    'twilio_auth_token' => env('TWILIO_AUTH_TOKEN',false),
    'twilio_verify_sid' => env('TWILIO_VERIFY_SID',false),

];
