<?php

/*
 * This file is part of Zippy.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Zippy\Parser;

use Alchemy\Zippy\Exception\RuntimeException;

interface ParserInterface
{
    /**
     * Parses a file listing
     *
     * @param String $output The string to parse
     *
     * @return Array An array of Member properties (location, mtime, size & is_dir)
     *
     * @throws RuntimeException In case the parsing process failed
     */
    public function parseFileListing($output);

    /**
     * Parses the inflator binary version
     *
     * @param String $output
     *
     * @return String The version
     */
    public function parseInflatorVersion($output);

    /**
     * Parses the deflator binary version
     *
     * @param String $output
     *
     * @return String The version
     */
    public function parsedeflatorVersion($output);
}
