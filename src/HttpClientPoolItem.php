<?php

namespace Http\Client\Common;

use Psr\Http\Message\RequestInterface;
use Http\Client\Exception;

/**
 * A HttpClientPoolItem represent a HttpClient inside a Pool.
 *
 * It is disabled when a request failed and can be reenable after a certain number of seconds
 * It also keep tracks of the current number of request the client is currently sending (only usable for async method)
 *
 * @author Joel Wurtz <joel.wurtz@gmail.com>
 */
class HttpClientPoolItem extends HttpClientFlexible
{
    /** @var int Number of request this client is actually sending */
    private $sendingRequestCount = 0;

    /** @var bool Status of the http client */
    private $disabled = false;

    /** @var \DateTime Time when this client has been disabled */
    private $disabledAt;

    /** @var int|null Number of seconds after this client is reenable, by default null: never reenable this client */
    private $reenableAfter;

    /**
     * {@inheritdoc}
     *
     * @param null|int $reenableAfter Number of seconds after this client is reenable
     */
    public function __construct($client, $reenableAfter = null)
    {
        parent::__construct($client);

        $this->reenableAfter = $reenableAfter;
    }

    /**
     * [@inheritdoc}
     */
    public function sendRequest(RequestInterface $request)
    {
        if ($this->isDisabled()) {
            throw new Exception\RequestException('Cannot send request as this client as been disabled', $request);
        }

        try {
            ++$this->sendingRequestCount;
            $response = parent::sendRequest($request);
            --$this->sendingRequestCount;
        } catch (Exception $e) {
            $this->disable();
            --$this->sendingRequestCount;

            throw $e;
        }

        return $response;
    }

    /**
     * [@inheritdoc}
     */
    public function sendAsyncRequest(RequestInterface $request)
    {
        if ($this->isDisabled()) {
            throw new Exception\RequestException('Cannot send request as this client as been disabled', $request);
        }

        ++$this->sendingRequestCount;

        $promise = parent::sendAsyncRequest($request);

        return $promise->then(function ($response) {
            --$this->sendingRequestCount;

            return $response;
        }, function ($exception) {
            $this->disable();
            --$this->sendingRequestCount;

            throw $exception;
        });
    }

    /**
     * Get current number of request that is send by the underlying http client.
     *
     * @return int
     */
    public function getSendingRequestCount()
    {
        return $this->sendingRequestCount;
    }

    /**
     * Disable the current client.
     */
    protected function disable()
    {
        $this->disabled = true;
        $this->disabledAt = new \DateTime('now');
    }

    /**
     * Whether this client is disabled or not
     *
     * Will also reactivate this client if possible
     *
     * @return bool
     */
    public function isDisabled()
    {
        if ($this->disabled && null !== $this->reenableAfter) {
            // Reenable after a certain time
            $now = new \DateTime();

            if (($now->getTimestamp() - $this->disabledAt->getTimestamp()) >= $this->reenableAfter) {
                $this->disabled = false;
            }
        }

        return $this->disabled;
    }
}
