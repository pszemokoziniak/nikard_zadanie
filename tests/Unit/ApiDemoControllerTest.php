<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\ApiDemoController;
use App\Services\Rest\JsonPlaceholderClient;
use App\Services\Soap\CountryInfoClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SoapFault;

class ApiDemoControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testRestPostsReturnsOkWithCountAndData(): void
    {
        $mockClient = Mockery::mock(JsonPlaceholderClient::class);
        $sample = [
            ['id' => 1, 'title' => 'A'],
            ['id' => 2, 'title' => 'B'],
            ['id' => 3, 'title' => 'C'],
        ];
        $mockClient->shouldReceive('getPosts')
            ->once()
            ->with(3)
            ->andReturn($sample);

        $request = Request::create('/api/rest/posts', 'GET', ['limit' => 3]);

        $controller = new ApiDemoController();
        $response = $controller->restPosts($request, $mockClient);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());

        $payload = $response->getData(true);
        $this->assertTrue($payload['ok']);
        $this->assertSame(3, $payload['count']);
        $this->assertSame($sample, $payload['data']);
    }

    public function testRestPostsHandlesException(): void
    {
        $mockClient = Mockery::mock(JsonPlaceholderClient::class);
        $mockClient->shouldReceive('getPosts')
            ->once()
            ->with(5)
            ->andThrow(new \RuntimeException('boom'));

        $request = Request::create('/api/rest/posts', 'GET'); // brak limit -> domyślnie 5

        $controller = new ApiDemoController();
        $response = $controller->restPosts($request, $mockClient);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(502, $response->getStatusCode());

        $payload = $response->getData(true);
        $this->assertFalse($payload['ok']);
        $this->assertSame('REST fetch failed', $payload['error']);
        $this->assertStringContainsString('boom', $payload['message']);
    }

    public function testSoapCapitalReturnsOk(): void
    {
        $mockClient = Mockery::mock(CountryInfoClient::class);
        $mockClient->shouldReceive('getCapitalCity')
            ->once()
            ->with('pl')
            ->andReturn('Warsaw');

        $controller = new ApiDemoController();
        $response = $controller->soapCapital('pl', $mockClient);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());

        $payload = $response->getData(true);
        $this->assertTrue($payload['ok']);
        $this->assertSame('PL', $payload['code']); // powinno być uppercased
        $this->assertSame('Warsaw', $payload['capital']);
    }

    public function testSoapCapitalHandlesSoapFault(): void
    {
        $mockClient = Mockery::mock(CountryInfoClient::class);
        $mockClient->shouldReceive('getCapitalCity')
            ->once()
            ->with('xx')
            ->andThrow(new SoapFault('Client', 'Invalid country code'));

        $controller = new ApiDemoController();
        $response = $controller->soapCapital('xx', $mockClient);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(502, $response->getStatusCode());

        $payload = $response->getData(true);
        $this->assertFalse($payload['ok']);
        $this->assertSame('SOAP fetch failed', $payload['error']);
        $this->assertStringContainsString('Invalid country code', $payload['message']);
    }

    public function testSoapCapitalHandlesUnexpectedError(): void
    {
        $mockClient = Mockery::mock(CountryInfoClient::class);
        $mockClient->shouldReceive('getCapitalCity')
            ->once()
            ->with('de')
            ->andThrow(new \Exception('unexpected'));

        $controller = new ApiDemoController();
        $response = $controller->soapCapital('de', $mockClient);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(500, $response->getStatusCode());

        $payload = $response->getData(true);
        $this->assertFalse($payload['ok']);
        $this->assertSame('Unexpected error', $payload['error']);
        $this->assertStringContainsString('unexpected', $payload['message']);
    }
}
