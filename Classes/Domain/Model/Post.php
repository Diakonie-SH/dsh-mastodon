<?php

namespace DiakonieSH\DshMastodon\Domain\Model;

use DiakonieSH\DshMastodon\Dto\PostVisibility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class Post extends AbstractEntity
{
    public const string TABLE = 'tx_dshmastodon_domain_model_post';

    public bool $hidden = false;
    public ?Account $account = null;
    public PostVisibility $visibility = PostVisibility::Public;
    public string $bodytext = '';
    public int $publishAt = 0;
    public bool $published = false;
    public bool $unpublished = false;
    public string $url = '';
    public ?string $tablename = null;
    public ?int $uidForeign = null;
    /** @var ObjectStorage<FileReference>|null */
    public ?ObjectStorage $images = null;

    /** @var array<string, mixed> */
    public array $view = [];

    public function getIdempotencyKey(): string
    {
        return sha1(join('-', [
            $this->getUid(),
            $this->account?->getUid(),
            $this->bodytext,
        ]));
    }

    public function getAsRow(): array
    {
        return [
            'uid' => $this->uid,
            'pid' => $this->pid,
            'hidden' => $this->hidden ? 1 : 0,
            'tablename' => $this->tablename,
            'uid_foreign' => $this->uidForeign,
            'publish_at' => $this->publishAt,
            'bodytext' => $this->bodytext,
            'images' => $this->images?->count() ?? 0,
        ];
    }
}
