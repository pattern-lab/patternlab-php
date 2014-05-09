<?php

/*
 * This file is part of Zippy.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Zippy;

use Alchemy\Zippy\Adapter\AdapterInterface;
use Alchemy\Zippy\Adapter\AdapterContainer;
use Alchemy\Zippy\Archive\ArchiveInterface;
use Alchemy\Zippy\Exception\ExceptionInterface;
use Alchemy\Zippy\Exception\FormatNotSupportedException;
use Alchemy\Zippy\Exception\NoAdapterOnPlatformException;
use Alchemy\Zippy\Exception\RuntimeException;
use Alchemy\Zippy\FileStrategy\FileStrategyInterface;
use Alchemy\Zippy\FileStrategy\TarFileStrategy;
use Alchemy\Zippy\FileStrategy\TarBz2FileStrategy;
use Alchemy\Zippy\FileStrategy\TarGzFileStrategy;
use Alchemy\Zippy\FileStrategy\TB2FileStrategy;
use Alchemy\Zippy\FileStrategy\TBz2FileStrategy;
use Alchemy\Zippy\FileStrategy\TGzFileStrategy;
use Alchemy\Zippy\FileStrategy\ZipFileStrategy;

class Zippy
{
    public $adapters;
    private $strategies = array();

    public function __construct(AdapterContainer $adapters)
    {
        $this->adapters = $adapters;
    }

    /**
     * Creates an archive
     *
     * @param string                         $path
     * @param String|Array|\Traversable|null $files
     * @param Boolean                        $recursive
     * @param string|null                    $type
     *
     * @return ArchiveInterface
     *
     * @throws RuntimeException In case of failure
     */
    public function create($path, $files = null, $recursive = true, $type = null)
    {
        if (null === $type) {
            $type = $this->guessAdapterExtension($path);
        }

        try {
            return $this
                    ->getAdapterFor($this->sanitizeExtension($type))
                    ->create($path, $files, $recursive);
        } catch (ExceptionInterface $e) {
            throw new RuntimeException('Unable to create archive', $e->getCode(), $e);
        }
    }

    /**
     * Opens an archive.
     *
     * @param string $path
     *
     * @return ArchiveInterface
     *
     * @throws RuntimeException In case of failure
     */
    public function open($path)
    {
        $type = $this->guessAdapterExtension($path);

        try {
            return $this
                    ->getAdapterFor($this->sanitizeExtension($type))
                    ->open($path);
        } catch (ExceptionInterface $e) {
            throw new RuntimeException('Unable to open archive', $e->getCode(), $e);
        }
    }

    /**
     * Adds a strategy.
     *
     * The last strategy added is preferred over the other ones.
     * You can add a strategy twice ; when doing this, the first one is removed
     * when inserting the second one.
     *
     * @param FileStrategyInterface $strategy
     *
     * @return Zippy
     */
    public function addStrategy(FileStrategyInterface $strategy)
    {
        $extension = $this->sanitizeExtension($strategy->getFileExtension());

        if (!isset($this->strategies[$extension])) {
            $this->strategies[$extension] = array();
        }

        if (false !== $key = array_search($strategy, $this->strategies[$extension], true)) {
            unset($this->strategies[$extension][$key]);
        }

        array_unshift($this->strategies[$extension], $strategy);

        return $this;
    }

    /**
     * Returns the strategies as they are stored
     *
     * @return array
     */
    public function getStrategies()
    {
        return $this->strategies;
    }

    /**
     * Returns an adapter for a file extension
     *
     * @param string $extension The extension
     *
     * @return AdapterInterface
     *
     * @throws FormatNotSupportedException  When no strategy is defined for this extension
     * @throws NoAdapterOnPlatformException When no adapter is supported for this extension on this platform
     */
    public function getAdapterFor($extension)
    {
        $extension = $this->sanitizeExtension($extension);

        if (!$extension || !isset($this->strategies[$extension])) {
            throw new FormatNotSupportedException(sprintf('No strategy for %s extension', $extension));
        }

        foreach ($this->strategies[$extension] as $strategy) {
            foreach ($strategy->getAdapters() as $adapter) {
                if ($adapter->isSupported()) {
                    return $adapter;
                }
            }
        }

        throw new NoAdapterOnPlatformException(sprintf('No adapter available for %s on this platform', $extension));
    }

    /**
     * Creates Zippy and loads default strategies
     *
     * @return Zippy
     */
    public static function load()
    {
        $adapters = AdapterContainer::load();
        $factory = new static($adapters);

        $factory->addStrategy(new ZipFileStrategy($adapters));
        $factory->addStrategy(new TarFileStrategy($adapters));
        $factory->addStrategy(new TarGzFileStrategy($adapters));
        $factory->addStrategy(new TarBz2FileStrategy($adapters));
        $factory->addStrategy(new TB2FileStrategy($adapters));
        $factory->addStrategy(new TBz2FileStrategy($adapters));
        $factory->addStrategy(new TGzFileStrategy($adapters));

        return $factory;
    }

    /**
     * Sanitize an extension.
     *
     * Strips dot from the beginning, converts to lowercase and remove trailing
     * whitespaces
     *
     * @param string $extension
     *
     * @return string
     */
    private function sanitizeExtension($extension)
    {
        return ltrim(trim(mb_strtolower($extension)), '.');
    }

    /**
     * Finds an extension that has strategy registered given a file path
     *
     * Returns null if no matching strategy found.
     *
     * @param string $path
     *
     * @return string|null
     */
    private function guessAdapterExtension($path)
    {
        $path = strtolower(trim($path));

        foreach ($this->strategies as $extension => $strategy) {
            if ($extension === substr($path, (strlen($extension) * -1))) {
                return $extension;
            }
        }

        return null;
    }
}
