<?php

declare(strict_types=1);

namespace SPunktOnline\ContaoGoogleReviewsWidget\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpClientExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GoogleReviewsService
{
    private const REQUEST_TIMEOUT = 5.0;

    private const CACHE_LIFETIME = 86400;

    /**
     * After a failed request, Google is not asked again for this many seconds, so an
     * outage or an exhausted quota does not slow down every page view.
     */
    private const RETRY_DELAY = 900;

    public function __construct(
        private readonly string $cacheDir,
        private readonly LoggerInterface $logger,
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    public function getReviews(string|null $apiKey, string|null $placeId): array
    {
        $apiKey = trim((string) $apiKey);
        $placeId = trim((string) $placeId);

        if ('' === $placeId) {
            return $this->getEmptyResult();
        }

        $cacheFile = $this->getCacheFile($placeId);
        $cache = $this->readCache($cacheFile);
        $now = time();

        if (null !== $cache && null !== $cache['data'] && $now - $cache['timestamp'] <= self::CACHE_LIFETIME) {
            return $cache['data'];
        }

        if ('' === $apiKey) {
            return $this->getEmptyResult();
        }

        // Within the retry delay of a failed request, serve the last known values (or
        // nothing) instead of asking Google again.
        if (null !== $cache && null !== $cache['failed_at'] && $now - $cache['failed_at'] < self::RETRY_DELAY) {
            return $cache['data'] ?? $this->getEmptyResult();
        }

        $apiData = $this->fetchFromGoogle($apiKey, $placeId);

        if (null !== $apiData) {
            $this->writeCache($cacheFile, ['timestamp' => $now, 'data' => $apiData]);

            return $apiData;
        }

        $this->writeCache($cacheFile, [
            'timestamp' => $cache['timestamp'] ?? 0,
            'data' => $cache['data'] ?? null,
            'failed_at' => $now,
        ]);

        return $cache['data'] ?? $this->getEmptyResult();
    }

    private function fetchFromGoogle(string $apiKey, string $placeId): array|null
    {
        try {
            $response = $this->httpClient->request(
                'GET',
                'https://maps.googleapis.com/maps/api/place/details/json',
                [
                    'query' => [
                        'place_id' => $placeId,
                        'fields' => 'rating,user_ratings_total',
                        'key' => $apiKey,
                    ],
                    'timeout' => self::REQUEST_TIMEOUT,
                ],
            );

            $data = $response->toArray(false);
        } catch (HttpClientExceptionInterface $exception) {
            $this->logger->error(
                'Failed to fetch reviews from the Google Places API.',
                [
                    'place_id' => $placeId,
                    'exception' => $exception::class,
                    // Transport errors contain the request URL, including the API key
                    'message' => str_replace($apiKey, '***', $exception->getMessage()),
                ],
            );

            return null;
        }

        if (
            !isset($data['result']['rating'])
            || !isset($data['result']['user_ratings_total'])
        ) {
            $this->logger->error(
                'Google Places API returned an unexpected response.',
                [
                    'place_id' => $placeId,
                    'response' => $data,
                ],
            );

            return null;
        }

        return [
            'formatted_rating' => number_format((float) $data['result']['rating'], 1, '.', ''),
            'user_ratings_total' => (int) $data['result']['user_ratings_total'],
        ];
    }

    private function getCacheFile(string $placeId): string
    {
        return $this->cacheDir.'/google_reviews_'.preg_replace('/[^a-zA-Z0-9_-]/', '_', $placeId).'.json';
    }

    /**
     * @return array{timestamp: int, data: array|null, failed_at: int|null}|null
     */
    private function readCache(string $cacheFile): array|null
    {
        if (!is_file($cacheFile)) {
            return null;
        }

        $content = file_get_contents($cacheFile);

        if (!$content) {
            return null;
        }

        $cache = json_decode($content, true);

        if (!\is_array($cache) || !isset($cache['timestamp'])) {
            return null;
        }

        return [
            'timestamp' => (int) $cache['timestamp'],
            'data' => $this->isValidData($cache['data'] ?? null) ? $cache['data'] : null,
            'failed_at' => isset($cache['failed_at']) ? (int) $cache['failed_at'] : null,
        ];
    }

    private function isValidData(mixed $data): bool
    {
        return \is_array($data)
            && isset($data['formatted_rating'], $data['user_ratings_total'])
            && \is_string($data['formatted_rating'])
            && 1 === preg_match('/^\d(\.\d)?$/', $data['formatted_rating'])
            && \is_int($data['user_ratings_total']);
    }

    private function writeCache(string $cacheFile, array $cache): void
    {
        if (!is_dir($this->cacheDir) && !@mkdir($this->cacheDir, 0775, true) && !is_dir($this->cacheDir)) {
            $this->logger->warning('Could not create the Google reviews cache directory.', ['directory' => $this->cacheDir]);

            return;
        }

        $tmpFile = $cacheFile.'.'.uniqid('', true).'.tmp';
        $json = json_encode($cache, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if (false === @file_put_contents($tmpFile, $json) || !@rename($tmpFile, $cacheFile)) {
            @unlink($tmpFile);
            $this->logger->warning('Could not write the Google reviews cache file.', ['file' => $cacheFile]);
        }
    }

    private function getEmptyResult(): array
    {
        return [
            'formatted_rating' => '0.0',
            'user_ratings_total' => 0,
        ];
    }
}
