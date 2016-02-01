<?php

namespace Http\Client\Common;

use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Psr\Http\Message\RequestInterface;

abstract class HttpClientPool implements HttpAsyncClient, HttpClient
{
    /** @var HttpClientPoolItem[] */
    protected $clientPool = [];

    /**
     * Add a client to the pool
     *
     * @param HttpClientPoolItem $client
     */
    public function addHttpClient(HttpClientPoolItem $client)
    {
        $this->clientPool[] = $client;
    }

    /**
     * Return an http client given a specific strategy
     *
     * @return HttpClientPoolItem Return a http client that can do both sync or async
     */
    abstract public function chooseHttpClient();

    /**
     * {@inheritdoc}
     */
    public function sendAsyncRequest(RequestInterface $request)
    {
        $client = $this->chooseHttpClient();

        return $client->sendAsyncRequest($request);
    }

    /**
     * {@inheritdoc}
     */
    public function sendRequest(RequestInterface $request)
    {
        $client = $this->chooseHttpClient();

        return $client->sendRequest($request);
    }
}
