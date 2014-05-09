<?php

namespace Alchemy\Zippy\Adapter\BSDTar;

use Alchemy\Zippy\Adapter\Resource\ResourceInterface;
use Alchemy\Zippy\Exception\NotSupportedException;

class TarGzBSDTarAdapter extends TarBSDTarAdapter
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
