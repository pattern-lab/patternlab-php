<?php

/*
 * This file is part of Zippy.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Zippy\Resource;

use Alchemy\Zippy\Exception\InvalidArgumentException;
use Alchemy\Zippy\Resource\Teleporter\LocalTeleporter;
use Alchemy\Zippy\Resource\Teleporter\GuzzleTeleporter;
use Alchemy\Zippy\Resource\Teleporter\StreamTeleporter;
use Alchemy\Zippy\Resource\Teleporter\TeleporterInterface;

/**
 * A container of TeleporterInterface
 */
class TeleporterContainer extends \Pimple
{
    /**
     * Returns the appropriate TeleporterInterface given a Resource
     *
     * @param Resource $resource
     *
     * @return TeleporterInterface
     *
     * @throws InvalidArgumentException
     */
    public function fromResource(Resource $resource)
    {
        switch (true) {
            case is_resource($resource->getOriginal()):
                $teleporter = 'stream-teleporter';
                break;
            case is_string($resource->getOriginal()):
                $data = parse_url($resource->getOriginal());

                if (!isset($data['scheme']) || 'file' === $data['scheme']) {
                    $teleporter = 'local-teleporter';
                } elseif (in_array($data['scheme'], array('http', 'https'))) {
                    $teleporter = 'guzzle-teleporter';
                } else {
                    $teleporter = 'stream-teleporter';
                }
                break;
            default:
                throw new InvalidArgumentException('No teleporter found');
        }

        return $this[$teleporter];
    }

    /**
     * Instantiates TeleporterContainer and register default teleporters
     *
     * @return TeleporterContainer
     */
    public static function load()
    {
        $container = new static();

        $container['stream-teleporter'] = $container->share(function () {
            return StreamTeleporter::create();
        });
        $container['local-teleporter'] = $container->share(function () {
            return LocalTeleporter::create();
        });
        $container['guzzle-teleporter'] = $container->share(function () {
            return GuzzleTeleporter::create();
        });

        return $container;
    }
}
