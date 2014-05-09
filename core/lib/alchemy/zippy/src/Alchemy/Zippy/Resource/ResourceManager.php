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

use Alchemy\Zippy\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException as SfIOException;

class ResourceManager
{
    private $mapper;
    private $teleporter;
    private $filesystem;

    /**
     * Constructor
     *
     * @param RequestMapper      $mapper
     * @param ResourceTeleporter $teleporter
     * @param Filesystem         $filesystem
     */
    public function __construct(RequestMapper $mapper, ResourceTeleporter $teleporter, Filesystem $filesystem)
    {
        $this->mapper = $mapper;
        $this->filesystem = $filesystem;
        $this->teleporter = $teleporter;
    }

    /**
     * Handles an archival request.
     *
     * The request is an array of string|streams to compute in a context (current
     * working directory most of the time)
     * Some keys can be associative. In these cases, the key is used the target
     * for the file.
     *
     * @param String $context
     * @param Array  $request
     *
     * @return ResourceCollection
     *
     * @throws IOException In case of write failure
     */
    public function handle($context, array $request)
    {
        $collection = $this->mapper->map($context, $request);

        if (!$collection->canBeProcessedInPlace()) {
            $context = sprintf('%s/%s', sys_get_temp_dir(), uniqid('zippy_'));

            try {
                $this->filesystem->mkdir($context);
            } catch (SfIOException $e) {
                throw new IOException(sprintf('Could not create temporary folder %s', $context), $e->getCode(), $e);
            }

            foreach ($collection as $resource) {
                $this->teleporter->teleport($context, $resource);
            }

            $collection = new ResourceCollection($context, $collection->toArray(), true);
        }

        return $collection;
    }

    /**
     * This method must be called once the ResourceCollection has been processed.
     *
     * It will remove temporary files
     *
     * @todo this should be done in the __destruct method of ResourceCollection
     *
     * @param ResourceCollection $collection
     */
    public function cleanup(ResourceCollection $collection)
    {
        if ($collection->isTemporary()) {
            try {
                $this->filesystem->remove($collection->getContext());
            } catch (IOException $e) {
                // log this ?
            }
        }
    }

    /**
     * Creates a default ResourceManager
     *
     * @return ResourceManager
     */
    public static function create()
    {
        return new static(RequestMapper::create(), ResourceTeleporter::create(), new Filesystem());
    }
}
