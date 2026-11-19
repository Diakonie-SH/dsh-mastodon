<?php

namespace DiakonieSH\DshMastodon\Service;

use DiakonieSH\DshMastodon\Domain\Model\Post;
use DiakonieSH\DshMastodon\Domain\Repository\PostRepository;
use DiakonieSH\DshMastodon\Dto\ProviderWithToken;
use GuzzleHttp\Exception\ClientException;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

readonly class StatusService
{
    public function __construct(
        private ProviderService $providerService,
        private PostRepository $postRepository,
        private PersistenceManager $persistenceManager,
        private LoggerInterface $logger,
        private ImageService $imageService,
    ) {}

    public function unpublish(Post $post): bool
    {
        if (!$post->published) {
            return true;
        }

        $mastodon = $this->providerService->findByUid($post->account?->getUid());

        if (!$mastodon) {
            return false;
        }

        if (!preg_match('#^https://(?P<domain>[^/]+)/(?P<account>[^/]+)/(?P<id>\d+)#', $post->url, $matches)) {
            return false;
        }

        $request = $mastodon->provider->getAuthenticatedRequest(
            'DELETE',
            $mastodon->provider->getInstanceUrl() . '/api/v1/statuses/' . $matches['id'],
            $mastodon->token,
        );

        try {
            $response = $mastodon->provider->getHttpClient()->send($request);
            $result = json_decode($response->getBody()->getContents(), associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            if ($e instanceof ClientException) {
                $statusCode = $e->getResponse()->getStatusCode();

                if ($statusCode === 404) {
                    $this->updatePostAsUnpublished($post);
                    return true;
                }
            }

            $this->logger->error('Unpublish (' . $post->getUid() . '): ' . $e->getMessage());
            return false;
        }

        $this->updatePostAsUnpublished($post);
        return true;
    }

    protected function updatePostAsUnpublished(Post $post): void
    {
        $this->logger->info('Unpublished (' . $post->getUid() . '): ' . $post->url);

        $post->published = false;
        $post->url = '';
        $post->unpublished = true;

        $this->postRepository->update($post);
        $this->persistenceManager->persistAll();
    }

    public function publish(Post $post): bool
    {
        $mastodon = $this->providerService->findByUid($post->account?->getUid());

        if (!$mastodon) {
            return false;
        }

        $status = [
            'content_type' => 'text/plain',
            'media_ids' => [],
            'status' => str_replace("\r\n", "\n", $post->bodytext),
            // todo add multi-language abilities
            // 'language' => 'ISO 639-1 lang',
            'visibility' => $post->visibility->asString(),
        ];

        if ($post->images?->count() > 0) {
            foreach ($post->images as $image) {
                /** @var \TYPO3\CMS\Extbase\Domain\Model\FileReference $image */
                $id = $this->uploadMedia($mastodon, $image->getOriginalResource());

                if ($id) {
                    $status['media_ids'][] = $id;
                }
            }
        }

        $request = $mastodon->provider->getAuthenticatedRequest(
            'POST',
            $mastodon->provider->getInstanceUrl() . '/api/v1/statuses',
            $mastodon->token,
        );

        try {
            $response = $mastodon->provider->getHttpClient()->send($request, [
                'headers' => [
                    'Idempotency-Key' => $post->getIdempotencyKey(),
                ],
                'json' => $status,
            ]);

            $result = json_decode($response->getBody()->getContents(), associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            $this->logger->error('Publish (' . $post->getUid() . '): ' . $e->getMessage());
            return false;
        }

        // TODO make proper Status DTO from response

        $post->published = true;
        $post->url = $result['url'];

        $this->postRepository->update($post);
        $this->persistenceManager->persistAll();

        $this->logger->info('Published (' . $post->getUid() . '): ' . $post->url);

        return true;
    }

    public function uploadMedia(ProviderWithToken $mastodon, FileReference $reference): ?string
    {
        if ($reference->isMissing()) {
            return null;
        }

        $processed = $this->imageService->resize($reference, 1000, 1000);
        $localFile = $processed->getForLocalProcessing(writable: false);
        $fileHandle = fopen($localFile, 'r');

        if (false === $fileHandle) {
            $this->logger->error('Upload media (' . $reference->getUid() . '): Could not open file ' . $localFile);
            return null;
        }

        $request = $mastodon->provider->getAuthenticatedRequest(
            'POST',
            $mastodon->provider->getInstanceUrl() . '/api/v2/media',
            $mastodon->token,
        );

        try {
            $response = $mastodon->provider->getHttpClient()->send($request, [
                'headers' => [
                ],
                'multipart' => [
                    [
                        'name' => 'file',
                        'contents' => $fileHandle,
                    ],
                    [
                        'name' => 'description',
                        'contents' => $reference->getAlternative(),
                    ],
                ],
            ]);

            $result = json_decode($response->getBody()->getContents(), associative: true, flags: JSON_THROW_ON_ERROR);
            $this->waitForMediaProcessing($mastodon, $result['id']);
        } catch (\Throwable $e) {
            $this->logger->error('Upload media (' . $reference->getUid() . '): ' . $e->getMessage());
            return null;
        }

        return $result['id'];
    }

    protected function waitForMediaProcessing(ProviderWithToken $mastodon, string $id): bool
    {
        $request = $mastodon->provider->getAuthenticatedRequest(
            'GET',
            $mastodon->provider->getInstanceUrl() . '/api/v1/media/' . $id,
            $mastodon->token,
        );

        $i = 0;
        $tries = 5;
        $waits = [1, 1, 2, 3, 5];

        while ($i < $tries) {
            try {
                $response = $mastodon->provider->getHttpClient()->send($request);

                if ($response->getStatusCode() === 200) {
                    return true;
                }
            } catch (\Throwable $e) {
            }

            sleep($waits[$i]);
            $i++;
        }

        return false;
    }
}
