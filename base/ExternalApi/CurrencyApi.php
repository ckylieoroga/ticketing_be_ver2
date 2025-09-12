<?php

namespace Base\ExternalApi;

use GuzzleHttp\Client;

class CurrencyApi
{
    const BASE_URL = 'https://api.freecurrencyapi.com/v1/';
    const REQUEST_TIMEOUT_DEFAULT = 15; // seconds

    const API_KEY = 'fca_live_uLEhWVJeAy5BZWLRHtKZvwSNJEisZe5B5vM7n4wd';

    protected Client $httpClient;


    public function __construct(?array $settings = [])
    {
        $guzzle_opts = [
            'http_errors' => false,
            'headers' => $this->buildHeaders(),
            'timeout' => $settings['timeout'] ?? self::REQUEST_TIMEOUT_DEFAULT
        ];
        if (isset($settings['guzzle_opts'])) {
            $guzzle_opts = array_merge($guzzle_opts, $settings['guzzle_opts']);
        }
        $this->httpClient = new Client($guzzle_opts);
    }

    private function buildHeaders() : array {
        return [
            'user-agent' => 'Freecurrencyapi/PHP/0.1',
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'apikey' => self::API_KEY,
        ];
    }

    private function call(string $endpoint, ?array $query = []) {
        $url = self::BASE_URL . $endpoint;

        try {
            $response = $this->httpClient->request('GET', $url, [
                'query' => $query
            ]);
        } catch (\Exception $e) {
           return ['msg' => $e->getMessage()];
        }

        return json_decode($response->getBody(), true);
    }

    public function status() {
        return $this->call('status');
    }

    public function currencies(?array $query = []) {
        return $this->call('currencies', $query);
    }

    public function latest(?array $query = []) {
        return $this->call('latest', $query);
    }

    public function historical($query) {
        return $this->call('historical', $query);
    }


}
