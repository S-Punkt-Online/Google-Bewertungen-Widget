<?php

declare(strict_types=1);

namespace SPunktOnline\ContaoGoogleReviewsWidget\Tests\Service;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\NullLogger;
use SPunktOnline\ContaoGoogleReviewsWidget\Service\GoogleReviewsService;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class GoogleReviewsServiceTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir().'/google-reviews-test-'.uniqid('', true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->cacheDir)) {
            array_map('unlink', glob($this->cacheDir.'/*') ?: []);
            rmdir($this->cacheDir);
        }
    }

    public function testReturnsEmptyResultWhenPlaceIdIsMissing(): void
    {
        $service = new GoogleReviewsService($this->cacheDir, new NullLogger(), new MockHttpClient());

        $result = $service->getReviews('some-api-key', '');

        $this->assertSame(['formatted_rating' => '0.0', 'user_ratings_total' => 0], $result);
        $this->assertDirectoryDoesNotExist($this->cacheDir);
    }

    public function testReturnsEmptyResultWhenApiKeyIsMissingAndNoCacheExists(): void
    {
        // No responses configured: MockHttpClient throws if a request were attempted.
        $service = new GoogleReviewsService($this->cacheDir, new NullLogger(), new MockHttpClient());

        $result = $service->getReviews('', 'place-123');

        $this->assertSame(['formatted_rating' => '0.0', 'user_ratings_total' => 0], $result);
    }

    public function testReturnsCachedDataWithoutCallingTheApi(): void
    {
        mkdir($this->cacheDir, 0775, true);
        file_put_contents(
            $this->cacheDir.'/google_reviews_place-123.json',
            json_encode(['timestamp' => time(), 'data' => ['formatted_rating' => '4.2', 'user_ratings_total' => 10]]),
        );

        // No responses configured: MockHttpClient throws if a request were attempted.
        $service = new GoogleReviewsService($this->cacheDir, new NullLogger(), new MockHttpClient());

        $result = $service->getReviews('api-key', 'place-123');

        $this->assertSame(['formatted_rating' => '4.2', 'user_ratings_total' => 10], $result);
    }

    public function testFetchesFromGoogleAndWritesCacheWhenCacheIsExpired(): void
    {
        mkdir($this->cacheDir, 0775, true);
        file_put_contents(
            $this->cacheDir.'/google_reviews_place-123.json',
            json_encode(['timestamp' => time() - 90000, 'data' => ['formatted_rating' => '1.0', 'user_ratings_total' => 1]]),
        );

        $mockResponse = new MockResponse(json_encode([
            'result' => ['rating' => 4.7, 'user_ratings_total' => 128],
        ]));

        $service = new GoogleReviewsService($this->cacheDir, new NullLogger(), new MockHttpClient([$mockResponse]));

        $result = $service->getReviews('api-key', 'place-123');

        $this->assertSame(['formatted_rating' => '4.7', 'user_ratings_total' => 128], $result);

        $cached = json_decode(file_get_contents($this->cacheDir.'/google_reviews_place-123.json'), true);
        $this->assertSame(['formatted_rating' => '4.7', 'user_ratings_total' => 128], $cached['data']);
    }

    public function testReturnsEmptyResultAndLogsWhenApiResponseIsMalformed(): void
    {
        $mockResponse = new MockResponse(json_encode(['error_message' => 'Invalid request']));
        $logger = $this->createRecordingLogger();

        $service = new GoogleReviewsService($this->cacheDir, $logger, new MockHttpClient([$mockResponse]));

        $result = $service->getReviews('api-key', 'place-123');

        $this->assertSame(['formatted_rating' => '0.0', 'user_ratings_total' => 0], $result);
        $this->assertCount(1, $logger->records);
        $this->assertSame('error', $logger->records[0]['level']);
    }

    public function testReturnsEmptyResultAndLogsOnTransportError(): void
    {
        $mockResponse = new MockResponse('', ['error' => 'Connection timed out']);
        $logger = $this->createRecordingLogger();

        $service = new GoogleReviewsService($this->cacheDir, $logger, new MockHttpClient([$mockResponse]));

        $result = $service->getReviews('api-key', 'place-123');

        $this->assertSame(['formatted_rating' => '0.0', 'user_ratings_total' => 0], $result);
        $this->assertCount(1, $logger->records);
        $this->assertSame('error', $logger->records[0]['level']);
    }

    public function testRemovesTheApiKeyFromLoggedTransportErrors(): void
    {
        $mockResponse = new MockResponse('', ['error' => 'Idle timeout reached for "https://maps.googleapis.com/?key=secret-key".']);
        $logger = $this->createRecordingLogger();

        $service = new GoogleReviewsService($this->cacheDir, $logger, new MockHttpClient([$mockResponse]));
        $service->getReviews('secret-key', 'place-123');

        $this->assertStringNotContainsString('secret-key', json_encode($logger->records));
    }

    public function testServesExpiredDataWhenTheApiFails(): void
    {
        $this->writeCacheFile(['timestamp' => time() - 90000, 'data' => ['formatted_rating' => '4.2', 'user_ratings_total' => 10]]);

        $mockResponse = new MockResponse('', ['error' => 'Connection timed out']);
        $service = new GoogleReviewsService($this->cacheDir, new NullLogger(), new MockHttpClient([$mockResponse]));

        $result = $service->getReviews('api-key', 'place-123');

        $this->assertSame(['formatted_rating' => '4.2', 'user_ratings_total' => 10], $result);

        $cached = $this->readCacheFile();
        $this->assertSame(['formatted_rating' => '4.2', 'user_ratings_total' => 10], $cached['data']);
        $this->assertEqualsWithDelta(time(), $cached['failed_at'], 5);
    }

    public function testDoesNotRetryWithinTheRetryDelay(): void
    {
        $responses = [
            new MockResponse(json_encode(['status' => 'OVER_QUERY_LIMIT'])),
        ];

        // Only one response configured: MockHttpClient throws on a second request.
        $service = new GoogleReviewsService($this->cacheDir, new NullLogger(), new MockHttpClient($responses));

        $this->assertSame(['formatted_rating' => '0.0', 'user_ratings_total' => 0], $service->getReviews('api-key', 'place-123'));
        $this->assertSame(['formatted_rating' => '0.0', 'user_ratings_total' => 0], $service->getReviews('api-key', 'place-123'));
    }

    public function testRetriesAfterTheRetryDelay(): void
    {
        $this->writeCacheFile(['timestamp' => time() - 90000, 'data' => ['formatted_rating' => '4.2', 'user_ratings_total' => 10], 'failed_at' => time() - 1000]);

        $mockResponse = new MockResponse(json_encode(['result' => ['rating' => 4.8, 'user_ratings_total' => 11]]));
        $service = new GoogleReviewsService($this->cacheDir, new NullLogger(), new MockHttpClient([$mockResponse]));

        $this->assertSame(['formatted_rating' => '4.8', 'user_ratings_total' => 11], $service->getReviews('api-key', 'place-123'));
        $this->assertArrayNotHasKey('failed_at', $this->readCacheFile());
    }

    public function testIgnoresInvalidCachedData(): void
    {
        $this->writeCacheFile(['timestamp' => time(), 'data' => ['formatted_rating' => '<script>', 'user_ratings_total' => 10]]);

        $mockResponse = new MockResponse(json_encode(['result' => ['rating' => 4.8, 'user_ratings_total' => 11]]));
        $service = new GoogleReviewsService($this->cacheDir, new NullLogger(), new MockHttpClient([$mockResponse]));

        $this->assertSame(['formatted_rating' => '4.8', 'user_ratings_total' => 11], $service->getReviews('api-key', 'place-123'));
    }

    public function testLogsInsteadOfFailingWhenTheCacheCannotBeWritten(): void
    {
        // A file where the cache directory should be makes mkdir() fail
        touch($this->cacheDir);

        $mockResponse = new MockResponse(json_encode(['result' => ['rating' => 4.8, 'user_ratings_total' => 11]]));
        $logger = $this->createRecordingLogger();
        $service = new GoogleReviewsService($this->cacheDir, $logger, new MockHttpClient([$mockResponse]));

        try {
            $this->assertSame(['formatted_rating' => '4.8', 'user_ratings_total' => 11], $service->getReviews('api-key', 'place-123'));
            $this->assertSame('warning', $logger->records[0]['level']);
        } finally {
            unlink($this->cacheDir);
        }
    }

    private function writeCacheFile(array $cache): void
    {
        mkdir($this->cacheDir, 0775, true);
        file_put_contents($this->cacheDir.'/google_reviews_place-123.json', json_encode($cache));
    }

    private function readCacheFile(): array
    {
        return json_decode(file_get_contents($this->cacheDir.'/google_reviews_place-123.json'), true);
    }

    /**
     * @return LoggerInterface&object{records: array}
     */
    private function createRecordingLogger(): LoggerInterface
    {
        return new class implements LoggerInterface {
            use LoggerTrait;

            public array $records = [];

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $this->records[] = ['level' => $level, 'message' => $message, 'context' => $context];
            }
        };
    }
}
