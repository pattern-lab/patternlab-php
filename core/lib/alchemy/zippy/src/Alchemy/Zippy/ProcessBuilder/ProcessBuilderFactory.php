<?php

/*
 * This file is part of Zippy.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Zippy\ProcessBuilder;

use Alchemy\Zippy\Exception\InvalidArgumentException;
use Symfony\Component\Process\ProcessBuilder;

class ProcessBuilderFactory implements ProcessBuilderFactoryInterface
{
    /**
     * The binary path
     *
     * @var String
     */
    protected $binary;

    /**
     * Constructor
     *
     * @param String $binary The path to the binary
     *
     * @throws InvalidArgumentException In case binary path is invalid
     */
    public function __construct($binary)
    {
        $this->useBinary($binary);
    }

    /**
     * @inheritdoc
     */
    public function getBinary()
    {
        return $this->binary;
    }

    /**
     * @inheritdoc
     */
    public function useBinary($binary)
    {
        if (!is_executable($binary)) {
            throw new InvalidArgumentException(sprintf('`%s` is not an executable binary', $binary));
        }

        $this->binary = $binary;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function create()
    {
        if (null === $this->binary) {
            throw new InvalidArgumentException('No binary set');
        }

        return ProcessBuilder::create(array($this->binary))->setTimeout(null);
    }
}
