<?php

/*
 * This file is part of Zippy.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Zippy\FileStrategy;

use Alchemy\Zippy\Adapter\AdapterContainer;

class TarGzFileStrategy implements FileStrategyInterface
{
    private $container;

    public function __construct(AdapterContainer $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getAdapters()
    {
        return array(
            $this->container['Alchemy\\Zippy\\Adapter\\GNUTar\\TarGzGNUTarAdapter'],
            $this->container['Alchemy\\Zippy\\Adapter\\BSDTar\\TarGzBSDTarAdapter'],
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFileExtension()
    {
        return 'tar.gz';
    }
}
