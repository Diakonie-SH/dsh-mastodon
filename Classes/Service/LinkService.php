<?php

namespace DiakonieSH\DshMastodon\Service;

use DiakonieSH\DshMastodon\Domain\Model\Post;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Typolink\LinkFactory;
use TYPO3\CMS\Frontend\Typolink\LinkResultInterface;
use TYPO3\CMS\Frontend\Typolink\UnableToLinkException;

readonly class LinkService
{
    public function __construct(
        protected SiteFinder $siteFinder,
        protected LinkFactory $linkFactory,
    ) {}

    public function link(Post $post, array $configuration, ?ServerRequestInterface $request = null): ?LinkResultInterface
    {
        /** @var ServerRequestInterface $request */
        $request ??= $GLOBALS['TYPO3_REQUEST'];

        try {
            $site = $this->siteFinder->getSiteByPageId($configuration['pageUid'] ?? $post->getPid());
        } catch (SiteNotFoundException) {
            return null;
        }

        $request = $request->withAttribute('site', $site);

        /** @var ContentObjectRenderer $cObj */
        $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $cObj->setRequest($request);
        $cObj->start($post->getAsRow(), Post::TABLE);

        try {
            $result = $this->linkFactory->create('', $configuration, $cObj);
        } catch (UnableToLinkException) {
            return null;
        }

        return $result;
    }
}
