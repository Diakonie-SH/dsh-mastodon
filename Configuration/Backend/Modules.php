<?php

return [
    'mastodon' => [
        'parent' => 'web',
        // 'position' => ['after' => 'backend_user_management'],
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/module/mastodon',
        'iconIdentifier' => 'module-mastodon',
        'labels' => 'LLL:EXT:dsh_mastodon/Resources/Private/Language/locallang_module.xlf',
        'routes' => [
            '_default' => [
                'target' => \DiakonieSH\DshMastodon\Controller\ManagementController::class . '::handleRequest',
            ],
        ],
    ],
    'mastodon_overview' => [
        'parent' => 'mastodon',
        'access' => 'user',
        'path' => '/module/mastodon/overview',
        'labels' => [
            'title' => 'Overview',
        ],
        'routes' => [
            '_default' => [
                'target' => \DiakonieSH\DshMastodon\Controller\ManagementController::class . '::indexAction',
            ],
        ],
    ],
    'mastodon_admin' => [
        'parent' => 'mastodon',
        'access' => 'admin',
        'path' => '/module/mastodon/admin',
        'labels' => [
            'title' => 'Admin',
        ],
        'routes' => [
            '_default' => [
                'target' => \DiakonieSH\DshMastodon\Controller\ManagementController::class . '::adminAction',
            ],
        ],
    ],
];
