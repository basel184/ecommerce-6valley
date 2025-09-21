<?php

return [
    'name' => 'Theme Lifestyle',
    'route' => '',
    'url' => 'javascript:',
    'icon' => '<i class="fa-solid fa-screwdriver-wrench"></i>',
    'index' => 0,
    'path' => 'theme_route',
    'comfortable_panel_version' => '15.2',
    'route_list' => [
        [
            'name' => 'Promotional_Banners',
            'route' => 'admin.banner.list',
            'module_permission' => 'promotion_management',
            'url' => url('/') . '/admin/banner/list',
            'icon' => '<i class="tio-photo-square-outlined nav-icon"></i>',
            'path' => 'admin/banner/list',
            'route_list' => []
        ],
        [
            'name' => 'Most_Demanded_Product',
            'route' => 'admin.product-settings.inhouse-shop',
            'module_permission' => 'promotion_management',
            'url' => url('/') . '/admin/most-demanded',
            'icon' => '<i class="tio-chart-bar-4 nav-icon"></i>',
            'path' => 'admin/most-demanded',
            'route_list' => []
        ],
        [
            'name' => 'Features_Section',
            'route' => 'admin.pages-and-media.features-section',
            'module_permission' => 'business_settings',
            'url' => url('/') . '/admin/pages-and-media/features-section',
            'icon' => '<i class="tio-pages-outlined nav-icon"></i>',
            'path' => 'admin/pages-and-media/features-section',
            'route_list' => []
        ],
    ]
];
