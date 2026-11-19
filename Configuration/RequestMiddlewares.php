<?php

return [
    'backend' => [
        'diakonie-sh/mastodon/authorize' => [
            'target' => \DiakonieSH\DshMastodon\Middleware\MastodonAuthMiddleware::class,
            'after' => [
                'typo3/cms-core/normalized-params-attribute',
            ],
            'before' => [
                'typo3/cms-backend/backend-routing',
            ],
        ],
    ],
];
