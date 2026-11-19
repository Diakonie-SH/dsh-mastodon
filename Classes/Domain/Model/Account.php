<?php

namespace DiakonieSH\DshMastodon\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Account extends AbstractEntity
{
    public string $clientId;
    public string $clientSecret;
    public string $domain;
    public string $username = '';
    public string $redirectUrl;
    public ?string $authToken = null;
    public ?string $refreshToken = null;
    public int $expires;
    public string $ownerId = '';
    public ?string $owner = null;
    public string $state = '';
}
