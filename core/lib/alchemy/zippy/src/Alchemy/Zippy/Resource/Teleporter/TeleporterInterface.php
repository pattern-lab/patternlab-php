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

interface TeleporterInterface
{
    /**
     * Teleport a file from a destination to an other
     *
     * @param Resource $resource A Resource
     * @param string   $context  The current context
     *
     * @throws IOException              In case file could not be written on local
     * @throws InvalidArgumentException In case path to file is not valid
     */
    public function teleport(Resource $resource, $context);
}
