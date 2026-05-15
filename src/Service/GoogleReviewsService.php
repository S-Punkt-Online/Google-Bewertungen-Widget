<?php

namespace SPunktOnline\ContaoGoogleReviewsWidget\Service;

class GoogleReviewsService
{
  public function getReviews(?string $apiKey, ?string $placeId): array
  {
    $apiKey = trim((string) $apiKey);
    $placeId = trim((string) $placeId);

    if ($placeId === '') {
      return $this->getEmptyResult();
    }

    $cacheFile = $this->getCacheFile($placeId);

    $cachedData = $this->getCachedData($cacheFile);

    if ($cachedData !== null) {
      return $cachedData;
    }

    if ($apiKey === '') {
      return [
        'formatted_rating' => '4.7',
        'user_ratings_total' => 49,
      ];
    }

    $apiData = $this->fetchFromGoogle($apiKey, $placeId);

    if ($apiData !== null) {
      $this->saveCache($cacheFile, $apiData);

      return $apiData;
    }

    return $this->getEmptyResult();
  }

  private function fetchFromGoogle(string $apiKey, string $placeId): ?array
  {
    $url = 'https://maps.googleapis.com/maps/api/place/details/json?' . http_build_query([
        'place_id' => $placeId,
        'fields' => 'rating,user_ratings_total',
        'key' => $apiKey,
      ]);

    $response = @file_get_contents($url);

    if (!$response) {
      return null;
    }

    $data = json_decode($response, true);

    if (
      !isset($data['result']['rating']) ||
      !isset($data['result']['user_ratings_total'])
    ) {
      return null;
    }

    return [
      'formatted_rating' => number_format((float) $data['result']['rating'], 1, '.', ''),
      'user_ratings_total' => (int) $data['result']['user_ratings_total'],
    ];
  }

  private function getCacheFile(string $placeId): string
  {
    $directory = $_SERVER['DOCUMENT_ROOT'] . '/files/google-review-cache';

    if (!is_dir($directory)) {
      mkdir($directory, 0775, true);
    }

    return $directory . '/google_reviews_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $placeId) . '.json';
  }

  private function getCachedData(string $cacheFile): ?array
  {
    if (!file_exists($cacheFile)) {
      return null;
    }

    $content = file_get_contents($cacheFile);

    if (!$content) {
      return null;
    }

    $data = json_decode($content, true);

    if (!is_array($data)) {
      return null;
    }

    if (!isset($data['timestamp'], $data['data'])) {
      return null;
    }

    $cacheLifetime = 60 * 60 * 24;

    if ((time() - (int) $data['timestamp']) > $cacheLifetime) {
      return null;
    }

    if (!is_array($data['data'])) {
      return null;
    }

    return $data['data'];
  }

  private function saveCache(string $cacheFile, array $data): void
  {
    file_put_contents($cacheFile, json_encode([
      'timestamp' => time(),
      'data' => $data,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
  }

  private function getEmptyResult(): array
  {
    return [
      'formatted_rating' => '0.0',
      'user_ratings_total' => 0,
    ];
  }
}