<?php

namespace DiakonieSH\DshMastodon\Service;

use DiakonieSH\DshMastodon\Domain\Model\Account;
use DiakonieSH\DshMastodon\Domain\Repository\AccountRepository;
use DiakonieSH\DshMastodon\Dto\ProviderWithToken;
use League\OAuth2\Client\Token\AccessToken;
use Lrf141\OAuth2\Client\Provider\Mastodon;
use Lrf141\OAuth2\Client\Provider\MastodonResourceOwner;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

readonly class ProviderService
{
    public function __construct(
        private AccountRepository $accounts,
        private PersistenceManager $persistenceManager,
        private LoggerInterface $logger,
    ) {}

    public function add(string $clientId, string $clientSecret, string $redirect, string $domain, int $pid = 0): string
    {
        $account = new Account();
        $account->setPid($pid);
        $account->clientId = $clientId;
        $account->clientSecret = $clientSecret;
        $account->redirectUrl = $redirect;
        $account->domain = $domain;

        $provider = $this->fromAccount($account);
        $url = $provider->getAuthorizationUrl();
        $state = $provider->getState();
        $account->state = $state;

        $this->accounts->add($account);
        $this->persistenceManager->persistAll();

        return $url;
    }

    public function reAuthorize(int $uid, string $redirect): ?string
    {
        $existing = $this->findByUid($uid);

        if (!$existing) {
            return null;
        }

        $existing->account->redirectUrl = $redirect;
        $url = $existing->provider->getAuthorizationUrl();
        $state = $existing->provider->getState();

        $existing->account->state = $state;

        $this->accounts->update($existing->account);
        $this->persistenceManager->persistAll();

        return $url;
    }

    public function findByUid(int $uid): ?ProviderWithToken
    {
        /** @var ?Account $account */
        $account = $this->accounts->findByUid($uid);

        if (!$account || !$account->authToken) {
            return null;
        }

        $provider = $this->fromAccount($account);

        $options = [
            'access_token' => $account->authToken,
            'resource_owner_id' => $account->ownerId,
        ];

        if ($account->refreshToken) {
            $options['refresh_token'] = $account->refreshToken;
        }

        if ($account->expires) {
            $options['expires'] = $account->expires;
        }

        $token = new AccessToken($options);

        return new ProviderWithToken(
            $account,
            $provider,
            $token,
        );
    }

    public function completeAuthorization(string $state, string $code): ?ProviderWithToken
    {
        $account = $this->accounts->findByState($state);

        if (!$account) {
            return null;
        }

        $provider = $this->fromAccount($account);

        try {
            $token = $provider->getAccessToken('authorization_code', [
                'code' => $code,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Access token (account ' . $account->getUid() . '): ' . $e->getMessage());
            return null;
        }

        $account->authToken = $token->getToken();
        $account->refreshToken = $token->getRefreshToken();

        if ($token->getExpires()) {
            $account->expires = $token->getExpires();
        }

        try {
            $owner = $provider->getResourceOwner($token);
        } catch (\Throwable $e) {
            $this->logger->error('Resource owner (account ' . $account->getUid() . '): ' . $e->getMessage());
            return null;
        }

        $account->ownerId = $owner->getId();
        $account->owner = json_encode($owner->toArray());

        if ($owner instanceof MastodonResourceOwner) {
            $account->username = $owner->getName();
        }

        $this->accounts->update($account);
        $this->persistenceManager->persistAll();

        return new ProviderWithToken(
            $account,
            $provider,
            $token,
        );
    }

    protected function fromAccount(Account $account): Mastodon
    {
        return new Mastodon([
            'clientId' => $account->clientId,
            'clientSecret' => $account->clientSecret,
            'redirectUri' => $account->redirectUrl,
            'instance' => 'https://' . $account->domain,
            'scope' => 'read profile write:media write:statuses',
        ]);
    }
}
