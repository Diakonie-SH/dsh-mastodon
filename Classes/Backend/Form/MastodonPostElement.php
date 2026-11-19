<?php

namespace DiakonieSH\DshMastodon\Backend\Form;

use DiakonieSH\DshMastodon\Domain\Model\Post;
use DiakonieSH\DshMastodon\Domain\Repository\PostRepository;
use DiakonieSH\DshMastodon\Service\ImageService;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\DateFormatter;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class MastodonPostElement extends AbstractFormElement
{
    protected string $returnUrl = '';

    public function __construct(
        private readonly PostRepository $postRepository,
        private readonly UriBuilder $uriBuilder,
        private readonly ImageService $imageService,
    ) {}

    public function render(): array
    {
        $table = $this->data['tableName'];
        $uid = $this->data['vanillaUid'];
        $result = $this->initializeResultArray();

        if (!is_numeric($uid) && !($uid > 0)) {
            return $result;
        }

        $request = $this->getRequest();
        $this->returnUrl = $request->getAttribute('normalizedParams')->getRequestUri();
        $post = $this->postRepository->findByReferencedRecord($table, $uid);

        if (!$post) {
            $result['html'] .= $this->renderCreateAction($table, $uid);
        } else {
            $result['html'] .= $this->renderStatusInfo($post);
            $result['html'] .= $this->renderImages($post);
            $result['html'] .= $this->renderActions($post);
        }

        $result['javaScriptModules'][] = JavaScriptModuleInstruction::create('@dsh_mastodon/backend.js');
        $result['additionalInlineLanguageLabelFiles'][] = 'EXT:dsh_mastodon/Resources/Private/Language/locallang_javascript.xlf';

        return $result;
    }

    protected function renderCreateAction(mixed $table, mixed $uid): string
    {
        if (!$this->getBackendUser()->check('tables_modify', Post::TABLE)) {
            return '';
        }

        $createUri = $this->uriBuilder->buildUriFromRoute('mastodon_create_post', [
            'table' => $table,
            'uid' => $uid,
            'returnUrl' => $this->returnUrl,
        ]);

        $label = LocalizationUtility::translate('create', 'DshMastodon');

        return <<<HTML
            <a class="btn btn-primary js-publish" href="{$createUri}">{$label}</a>
            HTML;
    }

    protected function renderActions(Post $post): string
    {
        if (!$this->getBackendUser()->check('tables_modify', Post::TABLE)) {
            return '';
        }

        $html = ['<div class="btn-toolbar">'];

        if ($post->published) {
            $html[] = '<a class="btn btn-default" target="_blank" href="' . $post->url . '">' . LocalizationUtility::translate('view', 'DshMastodon') . '</a>';
        }

        $editUri = $this->uriBuilder->buildUriFromRoute('record_edit', [
            'edit' => [
                Post::TABLE => [
                    $post->getUid() => 'edit',
                ],
            ],
            'returnUrl' => $this->returnUrl,
        ]);

        $html[] = '<a class="btn btn-default" href="' . $editUri . '">' . LocalizationUtility::translate('edit', 'DshMastodon') . '</a>';

        $unpublishUri = $this->uriBuilder->buildUriFromRoute('mastodon_unpublish_post', [
            'uid' => $post->getUid(),
            'returnUrl' => $this->returnUrl,
        ]);

        $deleteUri = $this->uriBuilder->buildUriFromRoute('mastodon_delete_post', [
            'table' => $post->tablename,
            'uid' => $post->uidForeign,
            'returnUrl' => $this->returnUrl,
        ]);

        if ($post->published) {
            $html[] = '<a class="btn btn-danger js-unpublish-confirm" href="' . $unpublishUri . '" data-uid="{$post->getUid()}">';
            $html[] = LocalizationUtility::translate('unpublish', 'DshMastodon');
            $html[] = '</a>';
            $html[] = '<a class="btn btn-danger js-delete-confirm" href="' . $deleteUri . '" data-uid="{$post->getUid()}">';
            $html[] = LocalizationUtility::translate('unpublishAndDelete', 'DshMastodon');
        } else {
            $html[] = '<a class="btn btn-danger js-delete-confirm" href="' . $deleteUri . '" data-uid="{$post->getUid()}">';
            $html[] = LocalizationUtility::translate('delete', 'DshMastodon');
        }

        $html[] = '</a>';
        $html[] = '</div>';

        return join('', $html);
    }

    protected function renderStatusInfo(Post $post): string
    {
        $publishAt = '–';

        if ($post->publishAt) {
            $user = $this->getBackendUser();
            if ($user->user['lang'] ?? false) {
                $locale = GeneralUtility::makeInstance(Locales::class)->createLocale($user->user['lang']);
            } else {
                $locale = new Locale();
            }
            $publishAt = (new DateFormatter())->format($post->publishAt, 'LONG', $locale);
        }

        $publishLabel = LocalizationUtility::translate('publication', 'DshMastodon');
        $textLabel = LocalizationUtility::translate('text', 'DshMastodon');

        return <<<HTML
            <dl>
                <dt>{$publishLabel}:</dt>
                <dd>{$publishAt}</dd>
                <dt>{$textLabel}:</dt>
                <dd>
                <pre style="max-width: 800px; white-space: pre-line">{$post->bodytext}</pre>
                </dd>
            </dl>
            HTML;
    }

    protected function renderImages(Post $post): string
    {
        if (!($post->images?->count() > 0)) {
            return '';
        }

        $images = [];

        foreach ($post->images as $image) {
            $thumb = $this->imageService->thumbnail($image);

            if (!$thumb) {
                continue;
            }

            $images[] = '<div>' . $thumb . '</div>';
        }

        return '<div class="preview-thumbnails" style="margin-bottom: 1rem">' . join('', $images) . '</div>';
    }

    protected function getRequest(): ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'];
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
