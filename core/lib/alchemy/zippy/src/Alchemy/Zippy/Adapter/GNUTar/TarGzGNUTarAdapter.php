<?php

namespace Alchemy\Zippy\Adapter\GNUTar;

use Alchemy\Zippy\Adapter\Resource\ResourceInterface;
use Alchemy\Zippy\Exception\NotSupportedException;

class TarGzGNUTarAdapter extends TarGNUTarAdapter
{
    /**
     * @inheritdoc
     */
    protected function doAdd(ResourceInterface $resource, $files, $recursive)
    {
        throw new NotSupportedException('Updating a compressed tar archive is not supported.');
    }

    /**
     * @inheritdoc
     */
    protected function getLocalOptions()
    {
        return array('--gzip');
    }
}
