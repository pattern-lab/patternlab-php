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
use Alchemy\Zippy\Exception\InvalidArgumentException;

/**
 * This class transport an object using php stream wrapper
 */
class StreamTeleporter extends AbstractTeleporter
{
    /**
     * {@inheritdoc}
     */
    public function teleport(Resource $resource, $context)
    {
        $streamCreated = false;

        if (is_resource($resource->getOriginal())) {
            $stream = $resource->getOriginal();
        } else {
            $stream = @fopen($resource->getOriginal(), 'rb');

            if (!is_resource($stream)) {
                throw new InvalidArgumentException(sprintf(
                    'The stream or file "%s" could not be opened: ',
                    $resource->getOriginal()
                ));
            }
            $streamCreated = true;
        }

        $content = stream_get_contents($stream);

        if ($streamCreated) {
            fclose($stream);
        }

        $this->writeTarget($content, $resource, $context);
    }

    /**
     * Creates the StreamTeleporter
     *
     * @return StreamTeleporter
     */
    public static function create()
    {
        return new static();
    }
}
