<?php

namespace DiakonieSH\DshMastodon\Domain\Repository;

use DiakonieSH\DshMastodon\Domain\Model\Post;
use DiakonieSH\DshMastodon\Dto\PostVisibility;
use DiakonieSH\DshMastodon\Service\LinkService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\FileType;
use TYPO3\CMS\Core\Text\TextCropper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class PostRepository extends Repository
{
    protected $defaultOrderings = [
        'uid' => QueryInterface::ORDER_DESCENDING,
    ];

    public function __construct(
        protected AccountRepository $accountRepository,
        protected LinkService $linkService,
    ) {
        parent::__construct();
    }

    /**
     * @return QueryResultInterface<int, Post>|array
     */
    public function findPublishableEverywhere(): QueryResultInterface|array
    {
        $query = $this->createQuery();
        $query->matching($query->logicalAnd(
            $query->equals('hidden', false),
            $query->equals('published', false),
            $query->equals('unpublished', false),
            $query->greaterThan('publish_at', 0),
            $query->lessThanOrEqual('publish_at', time()),
        ));
        $query->getQuerySettings()->setRespectStoragePage(false);
        return $query->execute();
    }

    /**
     * @return QueryResultInterface<int, Post>|array
     */
    public function findAllIncludingHidden(): QueryResultInterface|array
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setIgnoreEnableFields(true);
        return $query->execute();
    }

    /**
     * @return QueryResultInterface<int, Post>|array
     */
    public function findAllEverywhere(bool $includeHidden = false): QueryResultInterface|array
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);

        if ($includeHidden) {
            $query->getQuerySettings()->setIgnoreEnableFields(true);
        }

        return $query->execute();
    }

    public function findByReferencedRecord(string $table, int $uid): ?Post
    {
        $query = $this->createQuery();
        $query->matching($query->logicalAnd(
            $query->equals('tablename', $table),
            $query->equals('uidForeign', $uid),
        ));
        $query->getQuerySettings()->setRespectStoragePage(false);
        return $query->execute()->getFirst();
    }

    public function createFromReferenceRecord(string $table, int $uid): ?Post
    {
        $post = new Post();
        $post->tablename = $table;
        $post->uidForeign = $uid;

        $row = BackendUtility::getRecord($table, $uid);

        if (!$row) {
            return null;
        }

        $existingPost = $this->findByReferencedRecord($table, $uid);

        if ($existingPost) {
            $this->updateFromReferenceRecord($existingPost, $row);
            return $existingPost;
        }

        $tsConfig = BackendUtility::getPagesTSconfig($row['pid']);
        $config = $tsConfig['tx_dshmastodon.'] ?? [];
        $tableConfig = $config[$table . '.'] ?? [];
        $account = null;

        if (isset($config['account'])) {
            $account = $this->accountRepository->findByUid((int)$config['account']);
        }

        if (!$account) {
            $account = $this->accountRepository->findAllEverywhere()->getFirst();
        }

        if (!$account) {
            return null;
        }

        $post->account = $account;
        $post->setPid($tableConfig['pid'] ?? $config['pid'] ?? $row['pid']);

        if (isset($tableConfig['visibility'])) {
            $visibility = PostVisibility::tryFrom((int)$tableConfig['visibility']) ?? PostVisibility::Public;
        } elseif (isset($config['visibility'])) {
            $visibility = PostVisibility::tryFrom((int)$config['visibility']) ?? PostVisibility::Public;
        } else {
            $visibility = PostVisibility::Public;
        }

        $post->visibility = $visibility;

        $this->updateMetadataFromReferenceRecord($post, $row);
        $this->updatePostTextFromReferenceRecord($post, $row);

        $this->add($post);
        $this->persistenceManager->persistAll();

        if (isset($tableConfig['imageField'])) {
            $this->addImagesToPost($table, $tableConfig['imageField'], $row, $post);
        }

        return $post;
    }

    public function updateFromReferenceRecord(Post $post, array $row): void
    {
        $this->updateMetadataFromReferenceRecord($post, $row);
        $this->updatePostTextFromReferenceRecord($post, $row);
        $this->save($post);
        $this->updateImagesFromReferenceRecord($post, $row);
    }

    protected function updateMetadataFromReferenceRecord(Post $post, array $row): void
    {
        $table = $post->tablename;
        $post->publishAt = time();

        if (isset($GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['starttime'])) {
            $startTimeColumn = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['starttime'];

            if ($row[$startTimeColumn]) {
                $post->publishAt = $row[$startTimeColumn];
            }
        }

        $tsConfig = BackendUtility::getPagesTSconfig($row['pid']);
        $config = $tsConfig['tx_dshmastodon.'] ?? [];
        $tableConfig = $config[$table . '.'] ?? [];

        $delay = $tableConfig['delay'] ?? $config['delay'] ?? '+1 hour';
        $post->publishAt = strtotime($delay, $post->publishAt);

        if (isset($GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'])) {
            $hiddenColumn = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'];
            $post->hidden = (bool)$row[$hiddenColumn];
        }
    }

    protected function updatePostTextFromReferenceRecord(Post $post, array $row): bool
    {
        $table = $post->tablename;
        $tsConfig = BackendUtility::getPagesTSconfig($row['pid']);
        $config = $tsConfig['tx_dshmastodon.'] ?? [];
        $tableConfig = $config[$table . '.'] ?? [];
        $url = '';
        $linkConfig = $tableConfig['link.'] ?? $config['link.'] ?? [];

        if ($linkConfig) {
            $result = $this->linkService->link($post, $linkConfig);
            $url = $result?->getUrl();
        }

        $hashtags = $tableConfig['hashtags'] ?? $config['hashtags'] ?? '';

        if ($hashtags) {
            $tags = GeneralUtility::trimExplode(' ', $hashtags, true);

            foreach ($tags as $i => $tag) {
                $tags[$i] = '#' . $tag;
            }

            $hashtags = join(' ', $tags);
        }

        $before = $post->bodytext;

        if (isset($tableConfig['template']) || isset($config['template'])) {
            $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
            $data = $row;
            $data['_link'] = $url;
            $data['_hashtags'] = $hashtags;
            $cObj->start($data, $table);

            if (isset($tableConfig['template'])) {
                $post->bodytext = $cObj->cObjGetSingle($tableConfig['template'], $tableConfig['template.'] ?? []);
            } else {
                $post->bodytext = $cObj->cObjGetSingle($config['template'], $config['template.'] ?? '');
            }
        } else {
            $titleColumn = $GLOBALS['TCA'][$table]['ctrl']['label'];
            $post->bodytext = $row[$titleColumn];

            $nonTeaserTextLength = strlen($post->bodytext)
                + ($url ? 25 : 0)
                + ($hashtags ? 2 + strlen($hashtags) : 0);
            $teaserCrop = 495 - $nonTeaserTextLength;

            if ($row['teaser'] ?? '') {
                $teaser = GeneralUtility::makeInstance(TextCropper::class)
                    ->crop(
                        content: $row['teaser'],
                        numberOfChars: $teaserCrop,
                        replacementForEllipsis: '...',
                        cropToSpace: true,
                    );
                $post->bodytext .= "\n\n" . $teaser;
            }

            if ($url) {
                $post->bodytext .= "\n\n" . $url;
            }

            if ($hashtags) {
                $post->bodytext .= "\n\n" . $hashtags;
            }

            $post->bodytext = trim($post->bodytext);
        }

        return !($before === $post->bodytext);
    }

    protected function updateImagesFromReferenceRecord(Post $post, array $row): void
    {
        if ($post->images) {
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $cmdMap = [];

            foreach ($post->images as $imageReference) {
                $cmdMap['sys_file_reference'][$imageReference->getUid()]['delete'] = 1;
            }

            $dataHandler->start([], $cmdMap);
            $dataHandler->process_cmdmap();
        }

        $table = $post->tablename;
        $tsConfig = BackendUtility::getPagesTSconfig($row['pid']);
        $config = $tsConfig['tx_dshmastodon.'];
        $tableConfig = $config[$table . '.'] ?? [];

        if (isset($tableConfig['imageField'])) {
            $this->addImagesToPost($table, $tableConfig['imageField'], $row, $post);
        }
    }

    protected function addImagesToPost(string $table, string $imageField, array $row, Post $post): void
    {
        $fileReferences = BackendUtility::resolveFileReferences($table, $imageField, $row);

        if (!$fileReferences) {
            return;
        }

        $refs = [];
        $i = 1;

        foreach ($fileReferences as $reference) {
            if ($reference->isMissing()) {
                continue;
            }
            if ($reference->getProperty('hidden')) {
                continue;
            }

            if (!$reference->isType(FileType::IMAGE)) {
                // todo handle non-image media types such as videos
                continue;
            }

            if ($i > 4) {
                break;
            }

            $refRow = BackendUtility::getRecord('sys_file_reference', $reference->getUid(), 'alternative,crop');

            $refs[StringUtility::getUniqueId('NEW')] = [
                'pid' => $post->getPid(),
                'uid_local' => $reference->getOriginalFile()->getUid(),
                'uid_foreign' => $post->getUid(),
                'tablenames' => Post::TABLE,
                'fieldname' => 'images',
                'sorting_foreign' => $i++,
                'alternative' => $refRow['alternative'],
                'crop' => $refRow['crop'],
            ];
        }

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataMap = [
            'sys_file_reference' => $refs,
            Post::TABLE => [
                $post->getUid() => [
                    'images' => join(',', array_keys($refs)),
                ],
            ],
        ];
        $dataHandler->start($dataMap, []);
        $dataHandler->process_datamap();
    }

    public function remove($object)
    {
        parent::remove($object);
        $this->persistenceManager->persistAll();
    }

    public function save(Post $post): void
    {
        $this->update($post);
        $this->persistenceManager->persistAll();
    }
}
