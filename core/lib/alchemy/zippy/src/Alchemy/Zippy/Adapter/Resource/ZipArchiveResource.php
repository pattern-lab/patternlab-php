<?php

/*
 * This file is part of Zippy.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace Alchemy\Zippy\Adapter\Resource;

class ZipArchiveResource implements ResourceInterface
{
    private $archive;

    public function __construct(\ZipArchive $archive)
    {
        $this->archive = $archive;
    }

    /**
     * {@inheritdoc}
     */
    public function getResource()
    {
        return $this->archive;
    }
}
