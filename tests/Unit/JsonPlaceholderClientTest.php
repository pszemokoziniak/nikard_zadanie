<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Rest\JsonPlaceholderClient;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class JsonPlaceholderClientTest extends TestCase
{
    public function test_get_posts_returns_sliced_data(): void
    {
        $payload = [
            ['id' => 1], ['id' => 2], ['id' => 3], ['id' => 4],
        ];
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($payload)),
        ]);
        $client = new GuzzleClient(['handler' => HandlerStack::create($mock)]);

        $service = new JsonPlaceholderClient($client);
        $result = $service->getPosts(2);

        $this->assertCount(2, $result);
        $this->assertSame([['id' => 1], ['id' => 2]], $result);
    }

    /**
     * @throws GuzzleException
     */
    public function test_get_posts_with_zero_limit_returns_empty_array(): void
    {
        $payload = [
            ['id' => 1], ['id' => 2],
        ];
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($payload)),
        ]);
        $client = new GuzzleClient(['handler' => HandlerStack::create($mock)]);

        $service = new JsonPlaceholderClient($client);
        $result = $service->getPosts(0);

        $this->assertSame([], $result);
    }

    /**
     * @throws GuzzleException
     */
    public function test_get_posts_propagates_request_exception(): void
    {
        $mock = new MockHandler([
            function () {
                throw new RequestException('network error', new Request('GET', '/posts'));
            },
        ]);
        $client = new GuzzleClient(['handler' => HandlerStack::create($mock)]);

        $this->expectException(RequestException::class);

        $service = new JsonPlaceholderClient($client);
        $service->getPosts(3);
    }
}
