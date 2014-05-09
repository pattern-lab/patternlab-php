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

namespace Alchemy\Zippy\Adapter;

use Alchemy\Zippy\Archive\Archive;
use Alchemy\Zippy\Exception\InvalidArgumentException;
use Alchemy\Zippy\Resource\ResourceManager;
use Alchemy\Zippy\Adapter\VersionProbe\VersionProbeInterface;
use Alchemy\Zippy\Exception\RuntimeException;
use Alchemy\Zippy\Adapter\Resource\ResourceInterface;

abstract class AbstractAdapter implements AdapterInterface
{
    /** @var ResourceManager */
    protected $manager;

    /**
     * The version probe
     *
     * @var VersionProbeInterface
     */
    protected $probe;

    public function __construct(ResourceManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @inheritdoc
     */
    public function open($path)
    {
        $this->requireSupport();

        return new Archive($this->createResource($path), $this, $this->manager);
    }

    /**
     * @inheritdoc
     */
    public function create($path, $files = null, $recursive = true)
    {
        $this->requireSupport();

        return $this->doCreate($this->makeTargetAbsolute($path), $files, $recursive);
    }

    /**
     * @inheritdoc
     */
    public function listMembers(ResourceInterface $resource)
    {
        $this->requireSupport();

        return $this->doListMembers($resource);
    }

    /**
     * @inheritdoc
     */
    public function add(ResourceInterface $resource, $files, $recursive = true)
    {
        $this->requireSupport();

        return $this->doAdd($resource, $files, $recursive);
    }

    /**
     * @inheritdoc
     */
    public function remove(ResourceInterface $resource, $files)
    {
        $this->requireSupport();

        return $this->doRemove($resource, $files);
    }

    /**
     * @inheritdoc
     */
    public function extract(ResourceInterface $resource, $to = null)
    {
        $this->requireSupport();

        return $this->doExtract($resource, $to);
    }

    /**
     * @inheritdoc
     */
    public function extractMembers(ResourceInterface $resource, $members, $to = null)
    {
        $this->requireSupport();

        return $this->doExtractMembers($resource, $members, $to);
    }

    /**
     * Returns the version probe used by this adapter
     *
     * @return VersionProbeInterface
     */
    public function getVersionProbe()
    {
        return $this->probe;
    }

    /**
     * Sets the version probe used by this adapter
     *
     * @return VersionProbeInterface
     */
    public function setVersionProbe(VersionProbeInterface $probe)
    {
        $this->probe = $probe;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isSupported()
    {
        if (!$this->probe) {
            throw new RuntimeException(sprintf(
                'No version probe has been set on %s whereas it is required', get_class($this)
            ));
        }

        return VersionProbeInterface::PROBE_OK === $this->probe->getStatus();
    }

    /**
     * Throws an exception is the current adapter is not supported
     *
     * @throws RuntimeException
     */
    protected function requireSupport()
    {
        if (false === $this->isSupported()) {
            throw new RuntimeException(sprintf('%s is not supported on your system', get_class($this)));
        }
    }

    /**
     * Change current working directory to another
     *
     * @param string $target the target directory
     *
     * @return AdapterInterface
     *
     * @throws RuntimeException In case of failure
     */
    protected function chdir($target)
    {
        if (false === @chdir($target)) {
            throw new RuntimeException(sprintf('Unable to chdir to `%s`', $target));
        }

        return $this;
    }

    /**
     * Creates a resource given a path
     *
     * @return ResourceInterface
     */
    abstract protected function createResource($path);

    /**
     * Do the removal after having check that the current adapter is supported
     *
     * @return Array
     */
    abstract protected function doRemove(ResourceInterface $resource, $files);

    /**
     * Do the add after having check that the current adapter is supported
     *
     * @return Array
     */
    abstract protected function doAdd(ResourceInterface $resource, $files, $recursive);

    /**
     * Do the extract after having check that the current adapter is supported
     *
     * @return \SplFileInfo The extracted archive
     */
    abstract protected function doExtract(ResourceInterface $resource, $to);

    /**
     * Do the extract members after having check that the current adapter is supported
     *
     * @return \SplFileInfo The extracted archive
     */
    abstract protected function doExtractMembers(ResourceInterface $resource, $members, $to);

    /**
     * Do the list members after having check that the current adapter is supported
     *
     * @return Array
     */
    abstract protected function doListMembers(ResourceInterface $resource);

    /**
     * Do the create after having check that the current adapter is supported
     *
     * @return ArchiveInterface
     */
    abstract protected function doCreate($path, $file, $recursive);

    /**
     * Makes the target path absolute as the adapters might have a different directory
     *
     * @param $path The path to convert
     *
     * @return string The absolute path
     *
     * @throws InvalidArgumentException In case the path is not writable or does not exist
     */
    private function makeTargetAbsolute($path)
    {
        $directory = dirname($path);

        if (!is_dir($directory)) {
            throw new InvalidArgumentException(sprintf('Target path %s does not exist.', $directory));
        }
        if (!is_writable($directory)) {
            throw new InvalidArgumentException(sprintf('Target path %s is not writeable.', $directory));
        }

        return realpath($directory).'/'.basename ($path);
    }
}
