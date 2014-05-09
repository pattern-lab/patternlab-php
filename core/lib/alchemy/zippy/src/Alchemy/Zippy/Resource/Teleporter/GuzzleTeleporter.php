<?php

/*
 * This file is part of Zippy.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Zippy\Resource\Teleporter;

use Alchemy\Zippy\Resource\Resource;
use Alchemy\Zippy\Exception\RuntimeException;
use Guzzle\Http\Client;
use Guzzle\Plugin\Backoff\BackoffPlugin;
use Guzzle\Common\Event;

/**
 * Guzzle Teleporter implementation for HTTP resources
 */
class GuzzleTeleporter extends AbstractTeleporter
{
    private $client;

    /**
     * Constructor
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    public function teleport(Resource $resource, $context)
    {
        $target = $this->getTarget($context, $resource);

        $stream = fopen($target, 'w');
        $response = $this->client->get($resource->getOriginal(), null, $stream)->send();
        fclose($stream);

        if (!$response->isSuccessful()) {
            throw new RuntimeException(sprintf('Unable to fetch %s', $resource->getOriginal()));
        }

        return $this;
    }

    /**
     * Creates the GuzzleTeleporter
     *
     * @return GuzzleTeleporter
     */
    public static function create()
    {
        $client = new Client();

        $client->getEventDispatcher()->addListener('request.error', function (Event $event) {
            // override guzzle default behavior of throwing exceptions
            // when 4xx & 5xx responses are encountered
            $event->stopPropagation();
        }, -254);

        $client->addSubscriber(BackoffPlugin::getExponentialBackoff(5, array(500, 502, 503, 408)));

        return new static($client);
    }
}
