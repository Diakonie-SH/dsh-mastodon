<?php

namespace DiakonieSH\DshMastodon\Controller;

use DiakonieSH\DshMastodon\Domain\Model\Post;
use DiakonieSH\DshMastodon\Domain\Repository\PostRepository;
use DiakonieSH\DshMastodon\Service\StatusService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

#[AsController]
readonly class CrudController
{
    public function __construct(
        private FlashMessageService $flashMessageService,
        private PostRepository $postRepository,
        private StatusService $statusService,
    ) {}

    public function createAction(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();

        if (!$this->getBackendUser()->check('tables_modify', Post::TABLE)) {
            $this->message(LocalizationUtility::translate('crud.missing_create', 'DshMastodon'), LocalizationUtility::translate('missing_permission', 'DshMastodon'), ContextualFeedbackSeverity::WARNING);
            return new RedirectResponse($queryParams['returnUrl']);
        }

        $post = $this->postRepository->createFromReferenceRecord($queryParams['table'], (int)$queryParams['uid']);

        if ($post) {
            $this->message(LocalizationUtility::translate('crud.success_create', 'DshMastodon'), LocalizationUtility::translate('success', 'DshMastodon'), ContextualFeedbackSeverity::OK);
        } else {
            $this->message(LocalizationUtility::translate('crud.error_create', 'DshMastodon'), LocalizationUtility::translate('error', 'DshMastodon'), ContextualFeedbackSeverity::ERROR);
        }

        return new RedirectResponse($queryParams['returnUrl']);
    }

    public function deleteAction(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();

        if (!$this->getBackendUser()->check('tables_modify', Post::TABLE)) {
            $this->message(LocalizationUtility::translate('crud.missing_delete', 'DshMastodon'), LocalizationUtility::translate('missing_permission', 'DshMastodon'), ContextualFeedbackSeverity::WARNING);
            return new RedirectResponse($queryParams['returnUrl']);
        }

        $post = $this->postRepository->findByReferencedRecord($queryParams['table'], (int)$queryParams['uid']);

        if (!$post) {
            return new RedirectResponse($queryParams['returnUrl']);
        }

        $dh = GeneralUtility::makeInstance(DataHandler::class);
        $cmdMap = [];
        $cmdMap[Post::TABLE][$post->getUid()]['delete'] = 1;
        $dh->start([], $cmdMap);
        $dh->process_cmdmap();
        $dh->printLogErrorMessages();

        if (empty($dh->errorLog)) {
            $this->message(LocalizationUtility::translate('crud.success_delete', 'DshMastodon'), LocalizationUtility::translate('success', 'DshMastodon'), ContextualFeedbackSeverity::OK);
        }

        return new RedirectResponse($queryParams['returnUrl']);
    }

    public function publishAction(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();

        if (!$this->getBackendUser()->check('tables_modify', Post::TABLE)) {
            $this->message(LocalizationUtility::translate('crud.missing_publish', 'DshMastodon'), LocalizationUtility::translate('missing_permission', 'DshMastodon'), ContextualFeedbackSeverity::WARNING);
            return new RedirectResponse($queryParams['returnUrl']);
        }

        /** @var ?Post $post */
        $post = $this->postRepository->findByUid((int)$queryParams['uid']);

        if (!$post) {
            return new RedirectResponse($queryParams['returnUrl']);
        }

        if ($this->statusService->publish($post)) {
            $this->message(LocalizationUtility::translate('crud.success_publish', 'DshMastodon', [$post->url]), LocalizationUtility::translate('success', 'DshMastodon'), ContextualFeedbackSeverity::OK);
        } else {
            $this->message(LocalizationUtility::translate('crud.error_publish', 'DshMastodon'), LocalizationUtility::translate('error', 'DshMastodon'), ContextualFeedbackSeverity::ERROR);
        }

        return new RedirectResponse($queryParams['returnUrl']);
    }

    public function unpublishAction(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();

        if (!$this->getBackendUser()->check('tables_modify', Post::TABLE)) {
            $this->message(LocalizationUtility::translate('crud.missing_unpublish', 'DshMastodon'), LocalizationUtility::translate('missing_permission', 'DshMastodon'), ContextualFeedbackSeverity::WARNING);
            return new RedirectResponse($queryParams['returnUrl']);
        }

        /** @var ?Post $post */
        $post = $this->postRepository->findByUid((int)$queryParams['uid']);

        if (!$post) {
            return new RedirectResponse($queryParams['returnUrl']);
        }

        if ($this->statusService->unpublish($post)) {
            $this->message(LocalizationUtility::translate('crud.success_unpublish', 'DshMastodon'), LocalizationUtility::translate('success', 'DshMastodon'), ContextualFeedbackSeverity::OK);
        } else {
            $this->message(LocalizationUtility::translate('crud.error_unpublish', 'DshMastodon'), LocalizationUtility::translate('error', 'DshMastodon'), ContextualFeedbackSeverity::ERROR);
        }

        return new RedirectResponse($queryParams['returnUrl']);
    }

    protected function message(string $message, string $title, ContextualFeedbackSeverity $severity): void
    {
        /** @var FlashMessage $message */
        $message = GeneralUtility::makeInstance(FlashMessage::class, $message, $title, $severity, true);
        $this->flashMessageService->getMessageQueueByIdentifier()->addMessage($message);
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
