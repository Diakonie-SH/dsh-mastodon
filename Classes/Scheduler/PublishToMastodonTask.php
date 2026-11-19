<?php

namespace DiakonieSH\DshMastodon\Scheduler;

use DiakonieSH\DshMastodon\Domain\Repository\PostRepository;
use DiakonieSH\DshMastodon\Service\StatusService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

class PublishToMastodonTask extends AbstractTask
{
    public function execute(): bool
    {
        $statusService = $this->getStatusService();
        $posts = $this->getPostRepository()->findPublishableEverywhere();

        foreach ($posts as $post) {
            if (!$statusService->publish($post)) {
                return false;
            }
        }

        return true;
    }

    protected function getPostRepository(): PostRepository
    {
        return GeneralUtility::makeInstance(PostRepository::class);
    }

    protected function getStatusService(): StatusService
    {
        return GeneralUtility::makeInstance(StatusService::class);
    }
}
