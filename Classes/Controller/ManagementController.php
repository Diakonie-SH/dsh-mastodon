<?php

namespace DiakonieSH\DshMastodon\Controller;

use DiakonieSH\DshMastodon\Domain\Repository\AccountRepository;
use DiakonieSH\DshMastodon\Domain\Repository\PostRepository;
use DiakonieSH\DshMastodon\Middleware\MastodonAuthMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\PageRenderer;

#[AsController]
readonly class ManagementController
{
    public function __construct(
        private UriBuilder $uriBuilder,
        private PageRenderer $pageRenderer,
        private ModuleTemplateFactory $moduleTemplateFactory,
        private AccountRepository $accountRepository,
        private PostRepository $postRepository,
    ) {}

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        return $this->indexAction($request);
    }

    public function indexAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->moduleTemplateFactory->create($request);
        $view->makeDocHeaderModuleMenu();

        $returnUrl = $request->getAttribute('normalizedParams')->getRequestUri();
        $queryParams = $request->getQueryParams();
        $posts = ($queryParams['id'] ?? 0)
            ? $this->postRepository->findAllIncludingHidden()
            : $this->postRepository->findAllEverywhere(includeHidden: true);

        foreach ($posts as $post) {
            $post->view['editUri'] = $this->uriBuilder->buildUriFromRoute('record_edit', [
                'edit' => [
                    'tx_dshmastodon_domain_model_post' => [
                        $post->getUid() => 'edit',
                    ],
                ],
                'returnUrl' => $returnUrl,
            ]);
            if ($post->tablename && $post->uidForeign) {
                $post->view['editRecordUri'] = $this->uriBuilder->buildUriFromRoute('record_edit', [
                    'edit' => [
                        $post->tablename => [
                            $post->uidForeign => 'edit',
                        ],
                    ],
                    'returnUrl' => $returnUrl,
                ]);
            }
            $post->view['deleteUri'] = $this->uriBuilder->buildUriFromRoute('mastodon_delete_post', [
                'table' => $post->tablename,
                'uid' => $post->uidForeign,
                'returnUrl' => $returnUrl,
            ]);
            $post->view['publishUri'] = $this->uriBuilder->buildUriFromRoute('mastodon_publish_post', [
                'uid' => $post->getUid(),
                'returnUrl' => $returnUrl,
            ]);
            $post->view['unpublishUri'] = $this->uriBuilder->buildUriFromRoute('mastodon_unpublish_post', [
                'uid' => $post->getUid(),
                'returnUrl' => $returnUrl,
            ]);
        }

        $view->assign('posts', $posts);

        $this->pageRenderer->addInlineLanguageLabelFile('EXT:dsh_mastodon/Resources/Private/Language/locallang_javascript.xlf');

        return $view->renderResponse('Management/Overview');
    }

    public function adminAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->moduleTemplateFactory->create($request);
        $view->makeDocHeaderModuleMenu();

        $queryParams = $request->getQueryParams();
        $authParams = [];

        if (isset($queryParams['id'])) {
            $authParams['id'] = $queryParams['id'];
        }

        $uri = $request->getUri();
        $redirect = $uri->getScheme() . '://' . $uri->getHost() . MastodonAuthMiddleware::PATH;

        $view->assignMultiple([
            'authUri' => $this->uriBuilder->buildUriFromRoute('mastodon_auth', $authParams),
            'reauthUri' => $this->uriBuilder->buildUriFromRoute('mastodon_reauth', $authParams),
            'accounts' => $this->accountRepository->findAllEverywhere(),
            'redirect' => $redirect,
        ]);

        return $view->renderResponse('Management/Admin');
    }

    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
