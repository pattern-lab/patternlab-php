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

use Alchemy\Zippy\Adapter\Resource\FileResource;
use Alchemy\Zippy\Archive\MemberInterface;
use Alchemy\Zippy\Exception\InvalidArgumentException;
use Alchemy\Zippy\Exception\RuntimeException;
use Alchemy\Zippy\Parser\ParserInterface;
use Alchemy\Zippy\Parser\ParserFactory;
use Alchemy\Zippy\ProcessBuilder\ProcessBuilderFactoryInterface;
use Alchemy\Zippy\ProcessBuilder\ProcessBuilderFactory;
use Alchemy\Zippy\Resource\ResourceManager;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\ProcessBuilder;

abstract class AbstractBinaryAdapter extends AbstractAdapter implements BinaryAdapterInterface
{
    /**
     * The parser to use to parse command output
     *
     * @var ParserInterface
     */
    protected $parser;

    /**
     * The deflator process builder factory to use to build binary command line
     *
     * @var ProcessBuilderFactoryInterface
     */
    protected $deflator;

    /**
     * The inflator process builder factory to use to build binary command line
     *
     * @var ProcessBuilderFactoryInterface
     */
    protected $inflator;

    /**
     * Constructor
     *
     * @param ParserInterface                     $parser   An output parser
     * @param ResourceManager                     $manager  A resource manager
     * @param ProcessBuilderFactoryInterface      $inflator A process builder factory for the inflator binary
     * @param ProcessBuilderFactoryInterface|null $deflator A process builder factory for the deflator binary
     */
    public function __construct(ParserInterface $parser, ResourceManager $manager, ProcessBuilderFactoryInterface $inflator, ProcessBuilderFactoryInterface $deflator)
    {
        $this->parser = $parser;
        $this->manager = $manager;
        $this->deflator = $deflator;
        $this->inflator = $inflator;
    }

    /**
     * @inheritdoc
     */
    public function getParser()
    {
        return $this->parser;
    }

    /**
     * @inheritdoc
     */
    public function setParser(ParserInterface $parser)
    {
        $this->parser = $parser;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getDeflator()
    {
        return $this->deflator;
    }

    /**
     * @inheritdoc
     */
    public function getInflator()
    {
        return $this->inflator;
    }

    /**
     * @inheritdoc
     */
    public function setDeflator(ProcessBuilderFactoryInterface $processBuilder)
    {
        $this->deflator = $processBuilder;

        return $this;
    }

    public function setInflator(ProcessBuilderFactoryInterface $processBuilder)
    {
        $this->inflator = $processBuilder;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getInflatorVersion()
    {
        $this->requireSupport();

        return $this->doGetInflatorVersion();
    }

    /**
     * @inheritdoc
     */
    public function getDeflatorVersion()
    {
        $this->requireSupport();

        return $this->doGetDeflatorVersion();
    }

    /**
     * Returns a new instance of the invoked adapter
     *
     * @params String|null $inflatorBinaryName The inflator binary name to use
     * @params String|null $deflatorBinaryName The deflator binary name to use
     *
     * @return AbstractBinaryAdapter
     *
     * @throws RuntimeException In case object could not be instanciated
     */
    public static function newInstance(ExecutableFinder $finder, ResourceManager $manager, $inflatorBinaryName = null, $deflatorBinaryName = null)
    {
        $inflator = $inflatorBinaryName instanceof ProcessBuilderFactoryInterface ? $inflatorBinaryName : self::findABinary($inflatorBinaryName, static::getDefaultInflatorBinaryName(), $finder);
        $deflator = $deflatorBinaryName instanceof ProcessBuilderFactoryInterface ? $deflatorBinaryName : self::findABinary($deflatorBinaryName, static::getDefaultDeflatorBinaryName(), $finder);

        try {
            $outputParser = ParserFactory::create(static::getName());
        } catch (InvalidArgumentException $e) {
            throw new RuntimeException(sprintf(
                'Failed to get a new instance of %s',
                get_called_class()), $e->getCode(), $e
            );
        }

        if (null === $inflator) {
            throw new RuntimeException(sprintf('Unable to create the inflator'));
        }

        if (null === $deflator) {
            throw new RuntimeException(sprintf('Unable to create the deflator'));
        }

        return new static($outputParser, $manager, $inflator, $deflator);
    }

    private static function findABinary($wish, array $defaults, ExecutableFinder $finder)
    {
        $possibles = $wish ? (array) $wish : $defaults;

        $binary = null;

        foreach ($possibles as $possible) {
            if (null !== $found = $finder->find($possible)) {
                $binary = new ProcessBuilderFactory($found);
                break;
            }
        }

        return $binary;
    }

    /**
     * Adds files to argument list
     *
     * @param Array          $files   An array of files
     * @param ProcessBuilder $builder A Builder instance
     *
     * @return Boolean
     */
    protected function addBuilderFileArgument(array $files, ProcessBuilder $builder)
    {
        $iterations = 0;

        array_walk($files, function ($file) use ($builder, &$iterations) {
            $builder->add(
                $file instanceof \SplFileInfo ?
                $file->getRealpath() :
                ($file instanceof MemberInterface ? $file->getLocation() : $file)
            );

            $iterations++;
        });

        return 0 !== $iterations;
    }

    protected function createResource($path)
    {
        return new FileResource($path);
    }

    /**
     * Fetch the inflator version after having check that the current adapter is supported
     *
     * @return string
     */
    abstract protected function doGetInflatorVersion();

    /**
     * Fetch the Deflator version after having check that the current adapter is supported
     *
     * @return string
     */
    abstract protected function doGetDeflatorVersion();
}
