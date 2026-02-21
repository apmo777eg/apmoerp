<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ERP UI Mode
    |--------------------------------------------------------------------------
    |
    | When ERP_SIMPLE_MODE=true, the UI hides advanced/developer-focused pages
    | (template editors, scheduled report builders, module field builders, etc.)
    | to keep the system friendly for non-technical users.
    |
    | You can set ERP_SIMPLE_MODE=false to show every advanced page again.
    |
    */

    'simple_mode' => (bool) env('ERP_SIMPLE_MODE', true),

    // If true, hide pages that allow writing/typing code inside the admin panel.
    'hide_code_editors' => (bool) env('ERP_HIDE_CODE_EDITORS', true),

    // If true, hide extra "power user" sections in settings.
    'hide_advanced_settings' => (bool) env('ERP_HIDE_ADVANCED_SETTINGS', true),

    // If true, hide "link-only" settings tabs (currencies, exchange rates, translations)
    // and show them as quick links instead.
    'hide_settings_link_tabs' => (bool) env('ERP_HIDE_SETTINGS_LINK_TABS', true),
];