<?php

namespace Http\Client\Common\HttpClientPool;

use Http\Client\Common\HttpClientPool;
use Http\Client\Exception\TransferException;

/**
 * RoundRobinClientPool will choose the next client in the pool
 *
 * @author Joel Wurtz <joel.wurtz@gmail.com>
 */
class RoundRobinClientPool extends HttpClientPool
{
    /**
     * {@inheritdoc}
     */
    public function chooseHttpClient()
    {
        $last = current($this->clientPool);

        do {
            $client = next($this->clientPool);

            if (false === $client) {
                $client = reset($this->clientPool);

                if (false === $client) {
                    throw new TransferException('Cannot choose a http client as there is no one present in the pool');
                }
            }

            // Case when there is only one and the last one has been disabled
            if ($last === $client && $client->isDisabled()) {
                throw new TransferException('Cannot choose a http client as there is no one present in the pool');
            }
        } while($client->isDisabled());

        return $client;
    }
}
