<?php

namespace DiakonieSH\DshMastodon\Controller;

use DiakonieSH\DshMastodon\Middleware\MastodonAuthMiddleware;
use DiakonieSH\DshMastodon\Service\ProviderService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

#[AsController]
readonly class AuthController
{
    public function __construct(
        private UriBuilder $uriBuilder,
        private ProviderService $accounts,
        private FlashMessageService $flashMessageService,
    ) {}

    public function authAction(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() === 'POST') {
            $uri = $request->getUri();
            $redirect = $uri->getScheme() . '://' . $uri->getHost() . MastodonAuthMiddleware::PATH;
            $body = $request->getParsedBody();

            xdebug_break();
            $url = $this->accounts->add(
                trim($body['clientId']),
                trim($body['clientSecret']),
                $redirect,
                trim($body['domain']),
                $request->getQueryParams()['id'] ?? 0,
            );

            return new RedirectResponse($url, 303);
        }

        $queryParams = $request->getQueryParams();
        $result = null;

        if (isset($queryParams['code']) && isset($queryParams['state'])) {
            $result = $this->accounts->completeAuthorization($queryParams['state'], $queryParams['code']);
        }

        if ($result) {
            /** @var FlashMessage $message */
            $message = GeneralUtility::makeInstance(
                FlashMessage::class,
                LocalizationUtility::translate('auth.success', 'DshMastodon'),
                LocalizationUtility::translate('success', 'DshMastodon'),
                ContextualFeedbackSeverity::OK,
                true,
            );
        } else {
            /** @var FlashMessage $message */
            $message = GeneralUtility::makeInstance(
                FlashMessage::class,
                LocalizationUtility::translate('auth.error', 'DshMastodon'),
                LocalizationUtility::translate('error', 'DshMastodon'),
                ContextualFeedbackSeverity::ERROR,
                true,
            );
        }

        $this->flashMessageService->getMessageQueueByIdentifier()
            ->addMessage($message);

        $params = [];

        if ($result) {
            $params['id'] = $result->account->getPid();
        }

        return new RedirectResponse($this->uriBuilder->buildUriFromRoute('mastodon_admin', $params));
    }

    public function reauthAction(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() === 'POST') {
            $uri = $request->getUri();
            $redirect = $uri->getScheme() . '://' . $uri->getHost() . MastodonAuthMiddleware::PATH;
            $body = $request->getParsedBody();
            $url = $this->accounts->reAuthorize((int)$body['uid'], $redirect);

            if ($url) {
                return new RedirectResponse($url, 303);
            }
        }

        /** @var FlashMessage $message */
        $message = GeneralUtility::makeInstance(
            FlashMessage::class,
            LocalizationUtility::translate('reauth.error', 'DshMastodon'),
            LocalizationUtility::translate('error', 'DshMastodon'),
            ContextualFeedbackSeverity::ERROR,
            true,
        );

        $this->flashMessageService->getMessageQueueByIdentifier()
            ->addMessage($message);

        return new RedirectResponse($this->uriBuilder->buildUriFromRoute('mastodon_admin'));
    }
}
