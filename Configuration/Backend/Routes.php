<?php

return [
    'mastodon_auth' => [
        'path' => '/mastodon/auth',
        'target' => \DiakonieSH\DshMastodon\Controller\AuthController::class . '::authAction',
        'redirect' => [
            'enable' => true,
            'parameters' => ['code' => true, 'state' => true],
        ],
    ],
    'mastodon_reauth' => [
        'path' => '/mastodon/reauth',
        'target' => \DiakonieSH\DshMastodon\Controller\AuthController::class . '::reauthAction',
    ],
    'mastodon_create_post' => [
        'methods' => ['POST'],
        'path' => '/typo3/mastodon/post/create',
        'target' => \DiakonieSH\DshMastodon\Controller\CrudController::class . '::createAction',
    ],
    'mastodon_delete_post' => [
        'methods' => ['POST'],
        'path' => '/typo3/mastodon/post/delete',
        'target' => \DiakonieSH\DshMastodon\Controller\CrudController::class . '::deleteAction',
    ],
    'mastodon_publish_post' => [
        'methods' => ['POST'],
        'path' => '/typo3/mastodon/post/publish',
        'target' => \DiakonieSH\DshMastodon\Controller\CrudController::class . '::publishAction',
    ],
    'mastodon_unpublish_post' => [
        'methods' => ['POST'],
        'path' => '/typo3/mastodon/post/unpublish',
        'target' => \DiakonieSH\DshMastodon\Controller\CrudController::class . '::unpublishAction',
    ],
];
