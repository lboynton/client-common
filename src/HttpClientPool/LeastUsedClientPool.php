<?php

namespace Http\Client\Common\HttpClientPool;

use Http\Client\Common\HttpClientPool;
use Http\Client\Exception\TransferException;

/**
 * LeastUsedClientPool will choose the client with the less current request in the pool.
 *
 * This strategy is only useful when doing async request
 *
 * @author Joel Wurtz <joel.wurtz@gmail.com>
 */
class LeastUsedClientPool extends HttpClientPool
{
    /**
     * {@inheritdoc}
     */
    public function chooseHttpClient()
    {
        $clientPool = [];

        foreach ($this->clientPool as $clientPoolItem) {
            if (!$clientPoolItem->isDisabled()) {
                $clientPool[] = $clientPoolItem;
            }
        }

        if (empty($clientPool)) {
            throw new TransferException('Cannot choose a http client as there is no one present in the pool');
        }

        usort($clientPool, function ($clientA, $clientB) {
            if ($clientA->getSendingRequestCount() == $clientB->getSendingRequestCount()) {
                return 0;
            }

            if ($clientA->getSendingRequestCount() < $clientB->getSendingRequestCount()) {
                return -1;
            }

            return 1;
        });

        return reset($clientPool);
    }

}
