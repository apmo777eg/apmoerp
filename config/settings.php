<?php

declare(strict_types=1);

/**
 * Settings Configuration
 *
 * This file defines all available system settings organized by group.
 * Settings can be managed through the UI and stored in the database.
 */

return [
    /**
     * General Settings
     *
     * Basic company and system information
     */
    'general' => [
        'company_name' => [
            'label' => 'Company Name',
            'type' => 'string',
            'default' => 'HugousERP',
            'required' => true,
            'description' => 'Name of your company',
        ],
        'company_logo' => [
            'label' => 'Company Logo',
            'type' => 'file',
            'default' => null,
            'description' => 'Company logo (PNG, JPG, SVG)',
        ],
        'company_address' => [
            'label' => 'Company Address',
            'type' => 'textarea',
            'default' => '',
            'description' => 'Full company address',
        ],
        'company_phone' => [
            'label' => 'Company Phone',
            'type' => 'string',
            'default' => '',
            'description' => 'Primary contact phone number',
        ],
        'company_email' => [
            'label' => 'Company Email',
            'type' => 'email',
            'default' => '',
            'description' => 'Primary contact email',
        ],
        'default_language' => [
            'label' => 'Default Language',
            'type' => 'select',
            'options' => ['en' => 'English', 'ar' => 'العربية'],
            'default' => 'en',
            'description' => 'Default system language',
        ],
        'default_branch_id' => [
            'label' => 'Default Branch',
            'type' => 'select_branch',
            'default' => null,
            'description' => 'Default branch for new operations',
        ],
        'default_currency' => [
            'label' => 'Default Currency',
            'type' => 'select_currency',
            'default' => 'EGP',
            'description' => 'Default currency for transactions',
        ],
        'decimal_places' => [
            'label' => 'Decimal Places',
            'type' => 'integer',
            'default' => 2,
            'min' => 0,
            'max' => 4,
            'description' => 'Number of decimal places for amounts',
        ],
    ],

    /**
     * Branding & UI Settings
     *
     * User interface customization options
     */
    'branding' => [
        'theme' => [
            'label' => 'Theme',
            'type' => 'select',
            'options' => ['light' => 'Light', 'dark' => 'Dark', 'auto' => 'Auto'],
            'default' => 'light',
            'description' => 'Default color theme',
        ],
        'date_format' => [
            'label' => 'Date Format',
            'type' => 'select',
            'options' => [
                'Y-m-d' => 'YYYY-MM-DD',
                'd/m/Y' => 'DD/MM/YYYY',
                'm/d/Y' => 'MM/DD/YYYY',
                'd-m-Y' => 'DD-MM-YYYY',
            ],
            'default' => 'Y-m-d',
            'description' => 'Date display format',
        ],
        'time_format' => [
            'label' => 'Time Format',
            'type' => 'select',
            'options' => [
                'H:i:s' => '24-hour (HH:MM:SS)',
                'H:i' => '24-hour (HH:MM)',
                'h:i A' => '12-hour (hh:mm AM/PM)',
            ],
            'default' => 'H:i:s',
            'description' => 'Time display format',
        ],
        'report_default_view' => [
            'label' => 'Report Default View',
            'type' => 'select',
            'options' => ['day' => 'Daily', 'week' => 'Weekly', 'month' => 'Monthly', 'year' => 'Yearly'],
            'default' => 'month',
            'description' => 'Default period for reports',
        ],
    ],

    /**
     * POS Settings
     *
     * Point of Sale configuration
     */
    'pos' => [
        'allow_negative_stock' => [
            'label' => 'Allow Negative Stock',
            'type' => 'boolean',
            'default' => false,
            'description' => 'Allow selling products with insufficient stock',
        ],
        'max_discount_percent' => [
            'label' => 'Max Discount Percentage',
            'type' => 'number',
            'default' => 20,
            'min' => 0,
            'max' => 100,
            'description' => 'Maximum discount percentage per transaction',
        ],
        'max_discount_amount' => [
            'label' => 'Max Discount Amount',
            'type' => 'number',
            'default' => null,
            'description' => 'Maximum discount amount per transaction',
        ],
        'auto_print_receipt' => [
            'label' => 'Auto Print Receipt',
            'type' => 'boolean',
            'default' => true,
            'description' => 'Automatically print receipt after sale',
        ],
        'rounding_rule' => [
            'label' => 'Rounding Rule',
            'type' => 'select',
            'options' => [
                'none' => 'No Rounding',
                '0.05' => 'Round to 0.05',
                '0.10' => 'Round to 0.10',
                '0.25' => 'Round to 0.25',
                '0.50' => 'Round to 0.50',
                '1.00' => 'Round to 1.00',
            ],
            'default' => 'none',
            'description' => 'Cash rounding rule',
        ],
        'auto_open_cash_drawer' => [
            'label' => 'Auto Open Cash Drawer',
            'type' => 'boolean',
            'default' => true,
            'description' => 'Automatically open cash drawer on payment',
        ],
        'receipt_footer' => [
            'label' => 'Receipt Footer',
            'type' => 'textarea',
            'default' => 'Thank you for your business!',
            'description' => 'Text to display at the bottom of receipts',
        ],
    ],

    /**
     * Inventory & Products Settings
     *
     * Inventory management configuration
     */
    'inventory' => [
        'allow_negative_stock' => [
            'label' => 'Allow Negative Stock',
            'type' => 'boolean',
            'default' => false,
            'description' => 'Allow stock to go negative when insufficient quantity available',
        ],
        'default_costing_method' => [
            'label' => 'Default Costing Method',
            'type' => 'select',
            'options' => [
                'FIFO' => 'First In First Out (FIFO)',
                'LIFO' => 'Last In First Out (LIFO)',
                'AVG' => 'Average Cost',
            ],
            'default' => 'FIFO',
            'description' => 'Default inventory costing method',
        ],
        'default_warehouse_id' => [
            'label' => 'Default Warehouse',
            'type' => 'select_warehouse',
            'default' => null,
            'description' => 'Default warehouse for stock operations',
        ],
        'stock_alert_threshold' => [
            'label' => 'Stock Alert Threshold',
            'type' => 'number',
            'default' => 10,
            'min' => 0,
            'description' => 'Global low stock alert threshold',
        ],
        'use_per_product_threshold' => [
            'label' => 'Use Per-Product Threshold',
            'type' => 'boolean',
            'default' => true,
            'description' => 'Use individual product thresholds instead of global',
        ],
    ],

    /**
     * Sales & Invoicing Settings
     *
     * Sales and invoice configuration
     */
    'sales' => [
        'default_payment_terms' => [
            'label' => 'Default Payment Terms (Days)',
            'type' => 'integer',
            'default' => 30,
            'min' => 0,
            'description' => 'Default payment terms in days',
        ],
        'invoice_prefix' => [
            'label' => 'Invoice Prefix',
            'type' => 'string',
            'default' => 'INV-',
            'description' => 'Prefix for invoice numbers',
        ],
        'invoice_starting_number' => [
            'label' => 'Invoice Starting Number',
            'type' => 'integer',
            'default' => 1000,
            'min' => 1,
            'description' => 'Starting number for invoices',
        ],
        'default_tax_percent' => [
            'label' => 'Default Tax Percentage',
            'type' => 'number',
            'default' => 0,
            'min' => 0,
            'max' => 100,
            'description' => 'Default tax percentage if no tax specified',
        ],
        'auto_email_invoice' => [
            'label' => 'Auto Email Invoice',
            'type' => 'boolean',
            'default' => false,
            'description' => 'Automatically email invoice after saving',
        ],
    ],

    /**
     * Purchases Settings
     *
     * Purchase order configuration
     */
    'purchases' => [
        'require_approval' => [
            'label' => 'Require Approval Before Receive',
            'type' => 'boolean',
            'default' => false,
            'description' => 'Require approval before receiving purchases',
        ],
        'allow_edit_cost_after_receive' => [
            'label' => 'Allow Edit Cost After Receiving',
            'type' => 'boolean',
            'default' => false,
            'description' => 'Allow editing product cost after receiving',
        ],
        'purchase_order_prefix' => [
            'label' => 'Purchase Order Prefix',
            'type' => 'string',
            'default' => 'PO-',
            'description' => 'Prefix for purchase order numbers',
        ],
    ],

    /**
     * Rental Settings
     *
     * Rental management configuration
     */
    'rental' => [
        'grace_period_days' => [
            'label' => 'Grace Period (Days)',
            'type' => 'integer',
            'default' => 5,
            'min' => 0,
            'description' => 'Grace period after due date before penalty',
        ],
        'penalty_type' => [
            'label' => 'Penalty Type',
            'type' => 'select',
            'options' => [
                'percentage' => 'Percentage of Rent',
                'fixed' => 'Fixed Amount',
            ],
            'default' => 'percentage',
            'description' => 'Type of late payment penalty',
        ],
        'penalty_value' => [
            'label' => 'Penalty Value',
            'type' => 'number',
            'default' => 5,
            'min' => 0,
            'description' => 'Penalty percentage or fixed amount',
        ],
    ],

    /**
     * HRM & Payroll Settings
     *
     * Human resources configuration
     */
    'hrm' => [
        'working_days_per_week' => [
            'label' => 'Working Days Per Week',
            'type' => 'integer',
            'default' => 5,
            'min' => 1,
            'max' => 7,
            'description' => 'Standard working days per week',
        ],
        'working_hours_per_day' => [
            'label' => 'Working Hours Per Day',
            'type' => 'number',
            'default' => 8,
            'min' => 1,
            'max' => 24,
            'description' => 'Standard working hours per day',
        ],
        'late_arrival_threshold' => [
            'label' => 'Late Arrival Threshold (Minutes)',
            'type' => 'integer',
            'default' => 15,
            'min' => 0,
            'description' => 'Minutes after which arrival is considered late',
        ],
        'basic_tax_rate' => [
            'label' => 'Basic Tax Rate (%)',
            'type' => 'number',
            'default' => 0,
            'min' => 0,
            'max' => 100,
            'description' => 'Basic income tax rate',
        ],
        'transport_allowance_type' => [
            'label' => 'Transport Allowance Type',
            'type' => 'select',
            'options' => [
                'percentage' => 'Percentage of Basic',
                'fixed' => 'Fixed Amount',
            ],
            'default' => 'percentage',
            'description' => 'How transport allowance is calculated',
        ],
        'transport_allowance_value' => [
            'label' => 'Transport Allowance Value',
            'type' => 'number',
            'default' => 10,
            'min' => 0,
            'description' => 'Transport allowance percentage or fixed amount',
        ],
        'housing_allowance_type' => [
            'label' => 'Housing Allowance Type',
            'type' => 'select',
            'options' => [
                'percentage' => 'Percentage of Basic',
                'fixed' => 'Fixed Amount',
            ],
            'default' => 'percentage',
            'description' => 'How housing allowance is calculated',
        ],
        'housing_allowance_value' => [
            'label' => 'Housing Allowance Value',
            'type' => 'number',
            'default' => 0,
            'min' => 0,
            'description' => 'Housing allowance percentage or fixed amount',
        ],
        'meal_allowance' => [
            'label' => 'Meal Allowance (Fixed)',
            'type' => 'number',
            'default' => 0,
            'min' => 0,
            'description' => 'Fixed monthly meal allowance',
        ],
        'health_insurance_deduction' => [
            'label' => 'Health Insurance Deduction',
            'type' => 'number',
            'default' => 0,
            'min' => 0,
            'description' => 'Fixed monthly health insurance deduction',
        ],
    ],

    /**
     * Accounting Settings
     *
     * Accounting and financial configuration
     */
    'accounting' => [
        'default_coa_template' => [
            'label' => 'Default Chart of Accounts Template',
            'type' => 'select',
            'options' => [
                'standard' => 'Standard',
                'retail' => 'Retail',
                'service' => 'Service',
            ],
            'default' => 'standard',
            'description' => 'Default chart of accounts template',
        ],
        'account_sales_revenue' => [
            'label' => 'Sales Revenue Account',
            'type' => 'select_account',
            'default' => null,
            'description' => 'Default account for sales revenue',
        ],
        'account_purchase_expense' => [
            'label' => 'Purchase Expense Account',
            'type' => 'select_account',
            'default' => null,
            'description' => 'Default account for purchase expenses',
        ],
        'account_inventory' => [
            'label' => 'Inventory Account',
            'type' => 'select_account',
            'default' => null,
            'description' => 'Default account for inventory',
        ],
        'account_bank' => [
            'label' => 'Bank Account',
            'type' => 'select_account',
            'default' => null,
            'description' => 'Default bank account',
        ],
        'account_ar' => [
            'label' => 'Accounts Receivable',
            'type' => 'select_account',
            'default' => null,
            'description' => 'Default accounts receivable account',
        ],
        'account_ap' => [
            'label' => 'Accounts Payable',
            'type' => 'select_account',
            'default' => null,
            'description' => 'Default accounts payable account',
        ],
    ],

    /**
     * Integration Settings
     *
     * Third-party integrations (encrypted values)
     */
    'integrations' => [
        'shopify_api_key' => [
            'label' => 'Shopify API Key',
            'type' => 'string',
            'default' => null,
            'encrypted' => true,
            'description' => 'Shopify API key',
        ],
        'shopify_api_secret' => [
            'label' => 'Shopify API Secret',
            'type' => 'password',
            'default' => null,
            'encrypted' => true,
            'description' => 'Shopify API secret',
        ],
        'woocommerce_url' => [
            'label' => 'WooCommerce URL',
            'type' => 'url',
            'default' => null,
            'description' => 'WooCommerce store URL',
        ],
        'woocommerce_key' => [
            'label' => 'WooCommerce Consumer Key',
            'type' => 'string',
            'default' => null,
            'encrypted' => true,
            'description' => 'WooCommerce consumer key',
        ],
        'woocommerce_secret' => [
            'label' => 'WooCommerce Consumer Secret',
            'type' => 'password',
            'default' => null,
            'encrypted' => true,
            'description' => 'WooCommerce consumer secret',
        ],
        'paymob_api_key' => [
            'label' => 'Paymob API Key',
            'type' => 'password',
            'default' => null,
            'encrypted' => true,
            'description' => 'Paymob payment gateway API key',
        ],
        'stripe_secret_key' => [
            'label' => 'Stripe Secret Key',
            'type' => 'password',
            'default' => null,
            'encrypted' => true,
            'description' => 'Stripe secret key',
        ],
    ],

    /**
     * Notification Settings
     *
     * Notification preferences
     */

'mail' => [
    'label' => 'Email (SMTP)',
    'description' => 'Outgoing email server configuration',
    'settings' => [
        'smtp_host' => [
            'type' => 'string',
            'default' => '',
            'label' => 'SMTP Host',
            'description' => 'SMTP server host',
        ],
        'smtp_port' => [
            'type' => 'integer',
            'default' => 587,
            'label' => 'SMTP Port',
            'description' => 'SMTP server port',
        ],
        'smtp_username' => [
            'type' => 'string',
            'default' => '',
            'label' => 'SMTP Username',
            'description' => 'SMTP account username',
        ],
        'smtp_password' => [
            'type' => 'string',
            'default' => '',
            'label' => 'SMTP Password',
            'description' => 'SMTP account password',
            'encrypted' => true,
        ],
        'smtp_encryption' => [
            'type' => 'string',
            'default' => 'tls',
            'label' => 'Encryption',
            'description' => 'TLS/SSL/None',
            'options' => [
                'none' => 'None',
                'tls' => 'TLS',
                'ssl' => 'SSL',
            ],
        ],
        'from_address' => [
            'type' => 'string',
            'default' => '',
            'label' => 'From Address',
            'description' => 'Default sender email address',
        ],
        'from_name' => [
            'type' => 'string',
            'default' => '',
            'label' => 'From Name',
            'description' => 'Default sender name',
        ],
    ],
],

'whatsapp' => [
    'label' => 'WhatsApp',
    'description' => 'WhatsApp integration configuration',
    'settings' => [
        'enabled' => [
            'type' => 'boolean',
            'default' => false,
            'label' => 'Enabled',
            'description' => 'Enable WhatsApp integration',
        ],
        'provider' => [
            'type' => 'string',
            'default' => 'link',
            'label' => 'Provider',
            'description' => 'Choose how WhatsApp is used in the system',
            'options' => [
                'link' => 'WhatsApp Link (wa.me)',
                'cloud' => 'WhatsApp Cloud API',
            ],
        ],
        'default_country_code' => [
            'type' => 'string',
            'default' => '20',
            'label' => 'Default Country Code',
            'description' => 'Used when generating WhatsApp links/messages',
        ],
        'business_number' => [
            'type' => 'string',
            'default' => '',
            'label' => 'Business Number',
            'description' => 'Business phone number (without country code)',
        ],
        'cloud.phone_number_id' => [
            'type' => 'string',
            'default' => '',
            'label' => 'Phone Number ID',
            'description' => 'WhatsApp Cloud API Phone Number ID',
        ],
        'cloud.access_token' => [
            'type' => 'string',
            'default' => '',
            'label' => 'Access Token',
            'description' => 'WhatsApp Cloud API access token',
            'encrypted' => true,
        ],
        'cloud.api_version' => [
            'type' => 'string',
            'default' => 'v19.0',
            'label' => 'API Version',
            'description' => 'Graph API version (example: v19.0)',
        ],
    ],
],

    'notifications' => [
        'low_stock_enabled' => [
            'label' => 'Low Stock Alerts',
            'type' => 'boolean',
            'default' => true,
            'description' => 'Enable low stock notifications',
        ],
        'payment_due_enabled' => [
            'label' => 'Payment Due Alerts',
            'type' => 'boolean',
            'default' => true,
            'description' => 'Enable payment due notifications',
        ],
        'new_order_enabled' => [
            'label' => 'New Order Alerts',
            'type' => 'boolean',
            'default' => true,
            'description' => 'Enable new order notifications',
        ],
    ],

    /**
     * Advanced Performance Settings
     *
     * System performance and optimization options
     */
    'advanced' => [
        'cache_ttl' => [
            'label' => 'Cache TTL (seconds)',
            'type' => 'integer',
            'default' => 300,
            'min' => 60,
            'max' => 3600,
            'description' => 'Default cache time-to-live for dashboard and reports',
        ],
        'pagination_default' => [
            'label' => 'Default Pagination Size',
            'type' => 'select',
            'options' => [
                '10' => '10 items',
                '15' => '15 items',
                '25' => '25 items',
                '50' => '50 items',
                '100' => '100 items',
            ],
            'default' => '15',
            'description' => 'Default number of items per page in lists',
        ],
        'lazy_load_components' => [
            'label' => 'Lazy Load Components',
            'type' => 'boolean',
            'default' => true,
            'description' => 'Enable lazy loading for dashboard widgets and heavy components',
        ],
        'enable_smart_wire_keys' => [
            'label' => 'Enable Smart Wire Keys',
            'type' => 'boolean',
            'default' => true,
            'description' => 'Automatically generate unique keys for nested components',
        ],
        'spa_navigation_enabled' => [
            'label' => 'SPA Navigation (wire:navigate)',
            'type' => 'boolean',
            'default' => true,
            'description' => 'Enable SPA-like navigation between pages',
        ],
        'show_progress_bar' => [
            'label' => 'Show Navigation Progress Bar',
            'type' => 'boolean',
            'default' => true,
            'description' => 'Show progress bar during page navigation',
        ],
        'progress_bar_color' => [
            'label' => 'Progress Bar Color',
            'type' => 'color',
            'default' => '#22c55e',
            'description' => 'Color of the navigation progress bar',
        ],
        'max_payload_size' => [
            'label' => 'Max Payload Size (KB)',
            'type' => 'integer',
            'default' => 2048,
            'min' => 512,
            'max' => 10240,
            'description' => 'Maximum size of Livewire request payload in KB',
        ],
        'max_nesting_depth' => [
            'label' => 'Max Nested Form Depth',
            'type' => 'integer',
            'default' => 15,
            'min' => 5,
            'max' => 30,
            'description' => 'Maximum depth for nested form data (matches Livewire payload.max_nesting_depth)',
        ],
        'max_calls' => [
            'label' => 'Max Method Calls per Request',
            'type' => 'integer',
            'default' => 100,
            'min' => 20,
            'max' => 500,
            'description' => 'Maximum Livewire method calls per request (matches Livewire payload.max_calls)',
        ],
        'enable_query_logging' => [
            'label' => 'Enable Query Logging',
            'type' => 'boolean',
            'default' => false,
            'description' => 'Log slow database queries (development only)',
        ],
        'slow_query_threshold' => [
            'label' => 'Slow Query Threshold (ms)',
            'type' => 'integer',
            'default' => 100,
            'min' => 50,
            'max' => 5000,
            'description' => 'Log queries that take longer than this threshold',
        ],
    ],

    /**
     * UI/UX Enhancement Settings
     *
     * User interface customization
     */
    'ui' => [
        'sidebar_collapsed' => [
            'label' => 'Default Sidebar State',
            'type' => 'select',
            'options' => [
                'expanded' => 'Expanded',
                'collapsed' => 'Collapsed',
                'auto' => 'Auto (based on screen size)',
            ],
            'default' => 'auto',
            'description' => 'Default state of the sidebar navigation',
        ],
        'compact_tables' => [
            'label' => 'Compact Table Display',
            'type' => 'boolean',
            'default' => false,
            'description' => 'Use compact row height in data tables',
        ],
        'show_breadcrumbs' => [
            'label' => 'Show Breadcrumbs',
            'type' => 'boolean',
            'default' => true,
            'description' => 'Display navigation breadcrumbs',
        ],
        'enable_keyboard_shortcuts' => [
            'label' => 'Enable Keyboard Shortcuts',
            'type' => 'boolean',
            'default' => true,
            'description' => 'Enable keyboard shortcuts for common actions',
        ],
        'toast_position' => [
            'label' => 'Toast Notification Position',
            'type' => 'select',
            'options' => [
                'top-right' => 'Top Right',
                'top-left' => 'Top Left',
                'bottom-right' => 'Bottom Right',
                'bottom-left' => 'Bottom Left',
                'top-center' => 'Top Center',
                'bottom-center' => 'Bottom Center',
            ],
            'default' => 'top-right',
            'description' => 'Position of toast notifications',
        ],
        'toast_duration' => [
            'label' => 'Toast Duration (seconds)',
            'type' => 'integer',
            'default' => 5,
            'min' => 2,
            'max' => 30,
            'description' => 'How long toast notifications are displayed',
        ],
        'auto_save_forms' => [
            'label' => 'Auto-save Form Drafts',
            'type' => 'boolean',
            'default' => true,
            'description' => 'Automatically save form drafts to prevent data loss',
        ],
        'auto_save_interval' => [
            'label' => 'Auto-save Interval (seconds)',
            'type' => 'integer',
            'default' => 30,
            'min' => 10,
            'max' => 300,
            'description' => 'Interval between auto-save drafts',
        ],
    ],

    /**
     * Data Export Settings
     *
     * Export and report generation options
     */
    'export' => [
        'default_format' => [
            'label' => 'Default Export Format',
            'type' => 'select',
            'options' => [
                'xlsx' => 'Excel (XLSX)',
                'csv' => 'CSV',
                'pdf' => 'PDF',
            ],
            'default' => 'xlsx',
            'description' => 'Default format for data exports',
        ],
        'include_headers' => [
            'label' => 'Include Column Headers',
            'type' => 'boolean',
            'default' => true,
            'description' => 'Include column headers in exports',
        ],
        'max_export_rows' => [
            'label' => 'Max Export Rows',
            'type' => 'integer',
            'default' => 10000,
            'min' => 1000,
            'max' => 100000,
            'description' => 'Maximum number of rows in a single export',
        ],
        'chunk_size' => [
            'label' => 'Export Chunk Size',
            'type' => 'integer',
            'default' => 1000,
            'min' => 100,
            'max' => 5000,
            'description' => 'Number of records processed per batch during export',
        ],
        'pdf_orientation' => [
            'label' => 'PDF Orientation',
            'type' => 'select',
            'options' => [
                'portrait' => 'Portrait',
                'landscape' => 'Landscape',
            ],
            'default' => 'portrait',
            'description' => 'Default orientation for PDF exports',
        ],
        'pdf_paper_size' => [
            'label' => 'PDF Paper Size',
            'type' => 'select',
            'options' => [
                'a4' => 'A4',
                'letter' => 'Letter',
                'legal' => 'Legal',
                'a3' => 'A3',
            ],
            'default' => 'a4',
            'description' => 'Default paper size for PDF exports',
        ],
    ],
];
