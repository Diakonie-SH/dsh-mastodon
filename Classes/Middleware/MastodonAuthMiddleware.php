<?php

namespace DiakonieSH\DshMastodon\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Backend\Routing\RouteRedirect;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Http\RedirectResponse;

class MastodonAuthMiddleware implements MiddlewareInterface
{
    public const string PATH = '/typo3/mastodon_authorize';

    public function __construct(
        private readonly UriBuilder $uriBuilder,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getUri()->getPath() !== self::PATH) {
            return $handler->handle($request);
        }

        $loginRedirect = $this->uriBuilder->buildUriWithRedirect('login', redirect: RouteRedirect::create('mastodon_auth', $request->getQueryParams()));
        return new RedirectResponse($loginRedirect, 303);
    }
}
