<?php

namespace App\Services\Rest;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class JsonPlaceholderClient
{
    private Client $http;

    public function __construct(?Client $client = null)
    {
        $this->http = $client ?? new Client([
            'base_uri' => 'https://jsonplaceholder.typicode.com',
            'timeout' => 5.0,
        ]);
    }

    /**
     * Pobiera przykładową listę postów.
     *
     * @param  int  $limit  maksymalna liczba rekordów do zwrócenia
     *
     * @throws GuzzleException
     */
    public function getPosts(int $limit = 5): array
    {
        $res = $this->http->get('/posts');
        $data = json_decode((string) $res->getBody(), true) ?: [];

        return array_slice($data, 0, max(0, $limit));
    }
}
