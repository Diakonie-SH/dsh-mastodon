<?php

namespace DiakonieSH\DshMastodon\Dto;

use DiakonieSH\DshMastodon\Domain\Model\Account;
use League\OAuth2\Client\Token\AccessToken;
use Lrf141\OAuth2\Client\Provider\Mastodon;

class ProviderWithToken
{
    public function __construct(
        public Account $account,
        public Mastodon $provider,
        public AccessToken $token,
    ) {}
}
