<?php

namespace App\Http\Controllers;

use App\Services\Rest\JsonPlaceholderClient;
use App\Services\Soap\CountryInfoClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use SoapFault;

class ApiDemoController extends Controller
{
    public function restPosts(Request $request, JsonPlaceholderClient $client): JsonResponse
    {
        $limit = (int) $request->integer('limit', 5);
        try {
            $posts = $client->getPosts($limit);
            return new JsonResponse([
                'ok' => true,
                'count' => count($posts),
                'data' => $posts,
            ], 200);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'ok' => false,
                'error' => 'REST fetch failed',
                'message' => $e->getMessage(),
            ], 502);
        }
    }

    public function soapCapital(string $code, CountryInfoClient $client): JsonResponse
    {
        try {
            $capital = $client->getCapitalCity($code);
            return new JsonResponse([
                'ok' => true,
                'code' => strtoupper($code),
                'capital' => $capital,
            ], 200);
        } catch (SoapFault $e) {
            return new JsonResponse([
                'ok' => false,
                'error' => 'SOAP fetch failed',
                'message' => $e->getMessage(),
            ], 502);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'ok' => false,
                'error' => 'Unexpected error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
