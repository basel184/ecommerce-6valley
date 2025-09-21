<?php
// Payment gateway status and troubleshooting information
// Last updated: June 15, 2025

$gateways = [
    'myfatoorah' => [
        'status' => 'active',
        'last_checked' => '2025-06-15',
        'api_url' => 'https://api.myfatoorah.com',
        'notes' => 'Working properly'
    ],
    'tabby' => [
        'status' => 'active',
        'last_checked' => '2025-06-15',
        'api_url' => 'https://api.tabby.ai',
        'notes' => 'Working properly'
    ],
    'tamara' => [
        'status' => 'issues',
        'last_checked' => '2025-06-15',
        'api_url' => 'https://api.tamara.co',
        'notes' => 'Server returns 500 error. Fallback implemented.'
    ],
];

// Guide for troubleshooting:
// 1. Check API credentials in .env file
// 2. Ensure routes are properly set up
// 3. Verify webhook URLs are accessible from internet
// 4. Check logs at storage/logs/laravel.log
// 5. Contact payment gateway support

// Return gateway information
return $gateways;
