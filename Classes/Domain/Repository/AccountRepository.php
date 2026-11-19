<?php

namespace DiakonieSH\DshMastodon\Domain\Repository;

use DiakonieSH\DshMastodon\Domain\Model\Account;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

class AccountRepository extends Repository
{
    public function findByState(string $state): ?Account
    {
        $query = $this->createQuery();
        $query->matching($query->equals('state', $state));
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->setLimit(1);
        return $query->execute()->getFirst();
    }

    /**
     * @return QueryResultInterface<int, Account>|array
     */
    public function findAllEverywhere(): QueryResultInterface|array
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        return $query->execute();
    }
}
