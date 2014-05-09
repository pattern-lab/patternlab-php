<?php

/*
 * This file is part of Zippy.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Zippy\Adapter;

use Alchemy\Zippy\Archive\ArchiveInterface;
use Alchemy\Zippy\Adapter\Resource\ResourceInterface;
use Alchemy\Zippy\Exception\InvalidArgumentException;
use Alchemy\Zippy\Exception\RuntimeException;

Interface AdapterInterface
{
    /**
     * Opens an archive
     *
     * @param String $path The path to the archive
     *
     * @return ArchiveInterface
     *
     * @throws InvalidArgumentException In case the provided path is not valid
     * @throws RuntimeException         In case of failure
     */
    public function open($path);

    /**
     * Creates a new archive
     *
     * Please note some adapters can not create empty archives.
     * They would throw a `NotSupportedException` in case you ask to create an archive without files
     *
     * @param String                         $path      The path to the archive
     * @param String|Array|\Traversable|null $files     A filename, an array of files, or a \Traversable instance
     * @param Boolean                        $recursive Whether to recurse or not in the provided directories
     *
     * @return ArchiveInterface
     *
     * @throws RuntimeException         In case of failure
     * @throws NotSupportedException    In case the operation in not supported
     * @throws InvalidArgumentException In case no files could be added
     */
    public function create($path, $files = null, $recursive = true);

    /**
     * Tests if the adapter is supported by the current environment
     *
     * @return Boolean
     */
    public function isSupported();

    /**
     * Returns the list of all archive members
     *
     * @param ResourceInterface $resource The path to the archive
     *
     * @return Array
     *
     * @throws RuntimeException In case of failure
     */
    public function listMembers(ResourceInterface $resource);

    /**
     * Adds a file to the archive
     *
     * @param ResourceInterface         $resource  The path to the archive
     * @param String|Array|\Traversable $files     An array of paths to add, relative to cwd
     * @param Boolean                   $recursive Whether or not to recurse in the provided directories
     *
     * @return Array
     *
     * @throws RuntimeException         In case of failure
     * @throws InvalidArgumentException In case no files could be added
     */
    public function add(ResourceInterface $resource, $files, $recursive = true);

    /**
     * Removes a member of the archive
     *
     * @param ResourceInterface         $resource The path to the archive
     * @param String|Array|\Traversable $files    A filename, an array of files, or a \Traversable instance
     *
     * @return Array
     *
     * @throws RuntimeException         In case of failure
     * @throws InvalidArgumentException In case no files could be removed
     */
    public function remove(ResourceInterface $resource, $files);

    /**
     * Extracts an entire archive
     *
     * Note that any existing files will be overwritten by the adapter
     *
     * @param ResourceInterface $resource The path to the archive
     * @param String|null       $to       The path where to extract the archive
     *
     * @return \SplFileInfo The extracted archive
     *
     * @throws RuntimeException         In case of failure
     * @throws InvalidArgumentException In case the provided path where to extract the archive is not valid
     */
    public function extract(ResourceInterface $resource, $to = null);

    /**
     * Extracts specific members of the archive
     *
     * @param ResourceInterface $resource The path to the archive
     * @param Array             $members  An array of members
     * @param String|null       $to       The path where to extract the members
     *
     * @return \SplFileInfo The extracted archive
     *
     * @throws RuntimeException         In case of failure
     * @throws InvalidArgumentException In case no members could be removed or provide extract target directory is not valid
     */
    public function extractMembers(ResourceInterface $resource, $members, $to = null);

    /**
     * Returns the adapter name
     *
     * @return String
     */
    public static function getName();
}
