<?php

declare(strict_types=1);

namespace SPunktOnline\ContaoGoogleReviewsWidget\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpClientExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GoogleReviewsService
{
    private const REQUEST_TIMEOUT = 5.0;

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

        $cachedData = $this->getCachedData($cacheFile);

        if (null !== $cachedData) {
            return $cachedData;
        }

        if ('' === $apiKey) {
            return $this->getEmptyResult();
        }

        $apiData = $this->fetchFromGoogle($apiKey, $placeId);

        if (null !== $apiData) {
            $this->saveCache($cacheFile, $apiData);

            return $apiData;
        }

        return $this->getEmptyResult();
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
                    'exception' => $exception->getMessage(),
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
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0775, true);
        }

        return $this->cacheDir.'/google_reviews_'.preg_replace('/[^a-zA-Z0-9_-]/', '_', $placeId).'.json';
    }

    private function getCachedData(string $cacheFile): array|null
    {
        if (!file_exists($cacheFile)) {
            return null;
        }

        $content = file_get_contents($cacheFile);

        if (!$content) {
            return null;
        }

        $data = json_decode($content, true);

        if (!\is_array($data)) {
            return null;
        }

        if (!isset($data['timestamp'], $data['data'])) {
            return null;
        }

        $cacheLifetime = 60 * 60 * 24;

        if (time() - (int) $data['timestamp'] > $cacheLifetime) {
            return null;
        }

        if (!\is_array($data['data'])) {
            return null;
        }

        return $data['data'];
    }

    private function saveCache(string $cacheFile, array $data): void
    {
        $json = json_encode(
            [
                'timestamp' => time(),
                'data' => $data,
            ],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE,
        );

        $tmpFile = $cacheFile.'.'.uniqid('', true).'.tmp';

        file_put_contents($tmpFile, $json);
        rename($tmpFile, $cacheFile);
    }

    private function getEmptyResult(): array
    {
        return [
            'formatted_rating' => '0.0',
            'user_ratings_total' => 0,
        ];
    }
}
