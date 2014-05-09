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

use Symfony\Component\Process\ProcessBuilder;
use Alchemy\Zippy\Exception\InvalidArgumentException;

interface ProcessBuilderFactoryInterface
{
     /**
     * Returns a new instance of Symfony ProcessBuilder
     *
     * @return ProcessBuilder
     *
     * @throws InvalidArgumentException
     */
    public function create();

    /**
     * Returns the binary path
     *
     * @return String
     */
    public function getBinary();

    /**
     * Sets the binary path
     *
     * @param String $binary A binary path
     *
     * @return ProcessBuilderFactoryInterface
     *
     * @throws InvalidArgumentException In case binary is not executable
     */
    public function useBinary($binary);
}
