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
use Alchemy\Zippy\Exception\IOException;
use Alchemy\Zippy\Exception\InvalidArgumentException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException as SfIOException;

/**
 * This class transport an object using the local filesystem
 */
class LocalTeleporter extends AbstractTeleporter
{
    private $filesystem;

    /**
     * Constructor
     *
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritdoc}
     */
    public function teleport(Resource $resource, $context)
    {
        $target = $this->getTarget($context, $resource);
        $path = $resource->getOriginal();

        if (!file_exists($path)) {
            throw new InvalidArgumentException(sprintf('Invalid path %s', $path));
        }

        try {
            if (is_file($path)) {
                $this->filesystem->copy($path, $target);
            } elseif (is_dir($path)) {
                $this->filesystem->mirror($path, $target);
            } else {
                throw new InvalidArgumentException(sprintf('Invalid file or directory %s', $path));
            }
        } catch (SfIOException $e) {
            throw new IOException(sprintf('Could not write %s', $target), $e->getCode(), $e);
        }
    }

    /**
     * Creates the LocalTeleporter
     *
     * @return LocalTeleporter
     */
    public static function create()
    {
        return new static(new Filesystem());
    }
}
