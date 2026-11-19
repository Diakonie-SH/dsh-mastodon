<?php

namespace DiakonieSH\DshMastodon\Hooks;

use DiakonieSH\DshMastodon\Domain\Model\Post;
use DiakonieSH\DshMastodon\Domain\Repository\PostRepository;
use DiakonieSH\DshMastodon\Service\StatusService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DataHandlerHook
{
    /** @var array<array{table: string, uid: int}> */
    protected array $creations = [];
    /** @var array<array{table: string, uid: int, post: Post, row: array<string, mixed>}> */
    protected array $updates = [];
    protected PostRepository $postRepository;

    public function __construct()
    {
        $this->postRepository = GeneralUtility::makeInstance(PostRepository::class);
    }

    /**
     * Create a Mastodon post if necessary.
     *
     * @param string $status status
     * @param string $table table name
     * @param int $recordUid id of the record
     * @param array $fields fieldArray
     * @param DataHandler $parentObject parent Object
     */
    public function processDatamap_afterDatabaseOperations(
        $status,
        $table,
        $recordUid,
        array $fields,
        DataHandler $parentObject,
    ): void {
        if ($parentObject->BE_USER->workspace !== 0) {
            return;
        }

        if (!$parentObject->BE_USER->check('tables_modify', Post::TABLE)) {
            return;
        }

        $uid = $parentObject->substNEWwithIDs[$recordUid] ?? (int)$recordUid;
        $row = BackendUtility::getRecord($table, $uid);

        if (!$row) {
            return;
        }

        $post = $this->postRepository->findByReferencedRecord($table, $row['uid']);
        $tsConfig = BackendUtility::getPagesTSconfig($row['pid']);
        $autoUpdate = $tsConfig['tx_dshmastodon.'][$table . '.']['autoUpdate'] ?? '';

        if ($post) {
            if ($autoUpdate) {
                $this->updates[] = ['table' => $table, 'uid' => $row['uid'], 'post' => $post, 'row' => $row];
            }

            return;
        }

        $autoCreate = $tsConfig['tx_dshmastodon.'][$table . '.']['autoCreate'] ?? '';

        if (!$autoCreate) {
            return;
        }

        $autoCreateCutoff = $autoCreate;
        $crDate = date('Y-m-d', $row['crdate'] ?? time());

        if (is_numeric($autoCreate)) {
            $autoCreateCutoff = date('Y-m-d', (int)$autoCreate);
        }

        if ($crDate >= $autoCreateCutoff) {
            $this->creations[] = ['table' => $table, 'uid' => $row['uid']];
        }
    }

    /**
     * After all data operations: update and create post records.
     *
     * @param DataHandler $parentObject
     * @return void
     */
    public function processDatamap_afterAllOperations(DataHandler $parentObject): void
    {
        if ($parentObject->BE_USER->workspace !== 0) {
            return;
        }

        $creations = $this->creations;
        $updates = $this->updates;

        // ensure nested hook calls do not create duplicates
        $this->creations = [];
        $this->updates = [];

        foreach ($creations as $creation) {
            $this->postRepository->createFromReferenceRecord($creation['table'], $creation['uid']);
        }

        foreach ($updates as $update) {
            $this->postRepository->updateFromReferenceRecord($update['post'], $update['row']);
        }
    }

    /**
     * When the referenced record is deleted, the corresponding Mastodon Post should also be deleted.
     * If that post was already published, then we need to unpublish it.
     *
     * @param string $table
     * @param int $uid
     * @param array $recordToDelete
     * @param bool $recordWasDeleted
     * @param DataHandler $parentObject
     * @return void
     */
    public function processCmdmap_deleteAction(
        string $table,
        int $uid,
        array $recordToDelete,
        bool &$recordWasDeleted,
        DataHandler $parentObject,
    ): void {
        if ($parentObject->BE_USER->workspace !== 0) {
            return;
        }

        $statusService = GeneralUtility::makeInstance(StatusService::class);

        if ($table === Post::TABLE) {
            $post = $this->postRepository->findByUid($uid);
        } else {
            $post = $this->postRepository->findByReferencedRecord($table, $uid);
        }

        if (!($post instanceof Post)) {
            return;
        }

        if ($post->published && !$post->unpublished) {
            $statusService->unpublish($post);
        }

        // only delete via Extbase if DataHandler is deleting the reference post
        if ($table !== Post::TABLE) {
            $this->postRepository->remove($post);
        }
    }
}
