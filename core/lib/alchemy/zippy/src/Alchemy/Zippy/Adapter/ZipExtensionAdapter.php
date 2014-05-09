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

use Alchemy\Zippy\Exception\RuntimeException;
use Alchemy\Zippy\Exception\InvalidArgumentException;
use Alchemy\Zippy\Exception\NotSupportedException;
use Alchemy\Zippy\Archive\Member;
use Alchemy\Zippy\Adapter\Resource\ResourceInterface;
use Alchemy\Zippy\Adapter\Resource\ZipArchiveResource;
use Alchemy\Zippy\Adapter\VersionProbe\ZipExtensionVersionProbe;
use Alchemy\Zippy\Archive\Archive;
use Alchemy\Zippy\Resource\ResourceManager;
use Alchemy\Zippy\Resource\Resource;

/**
 * ZipExtensionAdapter allows you to create and extract files from archives
 * using PHP Zip extension
 *
 * @see http://www.php.net/manual/en/book.zip.php
 */
class ZipExtensionAdapter extends AbstractAdapter
{
    private $errorCodesMapping = array(
        \ZipArchive::ER_EXISTS => "File already exists",
        \ZipArchive::ER_INCONS => "Zip archive inconsistent",
        \ZipArchive::ER_INVAL  => "Invalid argument",
        \ZipArchive::ER_MEMORY => "Malloc failure",
        \ZipArchive::ER_NOENT  => "No such file",
        \ZipArchive::ER_NOZIP  => "Not a zip archive",
        \ZipArchive::ER_OPEN   => "Can't open file",
        \ZipArchive::ER_READ   => "Read error",
        \ZipArchive::ER_SEEK   => "Seek error"
    );

    public function __construct(ResourceManager $manager)
    {
        parent::__construct($manager);
        $this->probe = new ZipExtensionVersionProbe();
    }

    /**
     * @inheritdoc
     */
    protected function doListMembers(ResourceInterface $resource)
    {
        $members = array();
        for ($i = 0; $i < $resource->getResource()->numFiles; $i++) {
            $stat = $resource->getResource()->statIndex($i);
            $members[] = new Member(
                $resource,
                $this,
                $stat['name'],
                $stat['size'],
                new \DateTime('@' . $stat['mtime']),
                0 === strlen($resource->getResource()->getFromIndex($i, 1))
            );
        }

        return $members;
    }

    /**
     * @inheritdoc
     */
    public static function getName()
    {
        return 'zip-extension';
    }

    /**
     * @inheritdoc
     */
    protected function doExtract(ResourceInterface $resource, $to)
    {
        return $this->extractMembers($resource, null, $to);
    }

    /**
     * @inheritdoc
     */
    protected function doExtractMembers(ResourceInterface $resource, $members, $to)
    {
        if (null === $to) {
            // if no destination is given, will extract to zip current folder
            $to = dirname(realpath($resource->getResource()->filename));
        }
        if (!is_dir($to)) {
            $resource->getResource()->close();
            throw new InvalidArgumentException(sprintf("%s is not a directory", $to));
        }
        if (!is_writable($to)) {
            $resource->getResource()->close();
            throw new InvalidArgumentException(sprintf("%s is not writable", $to));
        }
        if (null !== $members) {
            $membersTemp = (array) $members;
            if (empty($membersTemp)) {
                $resource->getResource()->close();

                throw new InvalidArgumentException("no members provided");
            }
            $members = array();
            // allows $members to be an array of strings or array of Members
            foreach ($membersTemp as $member) {
                if ($member instanceof Member) {
                    $member = $member->getLocation();
                }
                if ($resource->getResource()->locateName($member) === false) {
                    $resource->getResource()->close();

                    throw new InvalidArgumentException(sprintf('%s is not in the zip file', $member));
                }
                $members[] = $member;
            }
        }

        if (!$resource->getResource()->extractTo($to, $members)) {
            $resource->getResource()->close();

            throw new InvalidArgumentException(sprintf('Unable to extract archive : %s', $resource->getResource()->getStatusString()));
        }

        return new \SplFileInfo($to);
    }

    /**
     * @inheritdoc
     */
    protected function doRemove(ResourceInterface $resource, $files)
    {
        $files = (array) $files;

        if (empty($files)) {
            throw new InvalidArgumentException("no files provided");
        }

        // either remove all files or none in case of error
        foreach ($files as $file) {
            if ($resource->getResource()->locateName($file) === false) {
                $resource->getResource()->unchangeAll();
                $resource->getResource()->close();

                throw new InvalidArgumentException(sprintf('%s is not in the zip file', $file));
            }
            if (!$resource->getResource()->deleteName($file)) {
                $resource->getResource()->unchangeAll();
                $resource->getResource()->close();

                throw new RuntimeException(sprintf('unable to remove %s', $file));
            }
        }
        $this->flush($resource->getResource());

        return $files;
    }

    /**
     * @inheritdoc
     */
    protected function doAdd(ResourceInterface $resource, $files, $recursive)
    {
        $files = (array) $files;
        if (empty($files)) {
            $resource->getResource()->close();
            throw new InvalidArgumentException("no files provided");
        }
        $this->addEntries($resource, $files, $recursive);

        return $files;
    }

    /**
     * @inheritdoc
     */
    protected function doCreate($path, $files, $recursive)
    {
        $files = (array) $files;

        if (empty($files)) {
            throw new NotSupportedException("Cannot create an empty zip");
        }

        $resource = $this->getResource($path, \ZipArchive::CREATE);
        $this->addEntries($resource, $files, $recursive);

        return new Archive($resource, $this, $this->manager);
    }

    /**
     * Returns a new instance of the invoked adapter
     *
     * @return AbstractAdapter
     *
     * @throws RuntimeException In case object could not be instanciated
     */
    public static function newInstance()
    {
        return new ZipExtensionAdapter(ResourceManager::create());
    }

    protected function createResource($path)
    {
        return $this->getResource($path, \ZipArchive::CHECKCONS);
    }

    private function getResource($path, $mode)
    {
        $zip = new \ZipArchive();
        $res = $zip->open($path, $mode);

        if ($res !== true) {
            throw new RuntimeException($this->errorCodesMapping[$res]);
        }

        return new ZipArchiveResource($zip);
    }

    private function addEntries(ZipArchiveResource $zipresource, array $files, $recursive)
    {
        $stack = new \SplStack();

        $error = null;
        $cwd = getcwd();
        $collection = $this->manager->handle($cwd, $files);

        $this->chdir($collection->getContext());

        $adapter = $this;

        try {
            $collection->forAll(function ($i, Resource $resource) use ($zipresource, $stack, $recursive, $adapter) {
                $adapter->checkReadability($zipresource->getResource(), $resource->getTarget());
                if (is_dir($resource->getTarget())) {
                    if ($recursive) {
                        $stack->push($resource->getTarget() . ((substr($resource->getTarget(), -1) === DIRECTORY_SEPARATOR) ? '' : DIRECTORY_SEPARATOR ));
                    } else {
                        $adapter->addEmptyDir($zipresource->getResource(), $resource->getTarget());
                    }
                } else {
                    $adapter->addFileToZip($zipresource->getResource(), $resource->getTarget());
                }

                return true;
            });

            // recursively add dirs
            while (!$stack->isEmpty()) {
                $dir = $stack->pop();
                // removes . and ..
                $files = array_diff(scandir($dir), array(".", ".."));
                if (count($files) > 0) {
                    foreach ($files as $file) {
                        $file = $dir . $file;
                        $this->checkReadability($zipresource->getResource(), $file);
                        if (is_dir($file)) {
                            $stack->push($file . DIRECTORY_SEPARATOR);
                        } else {
                            $this->addFileToZip($zipresource->getResource(), $file);
                        }
                    }
                } else {
                    $this->addEmptyDir($zipresource->getResource(), $dir);
                }
            }
            $this->flush($zipresource->getResource());

            $this->manager->cleanup($collection);
        } catch (\Exception $e) {
            $error = $e;
        }

        $this->chdir($cwd);

        if ($error) {
            throw $error;
        }
    }

    /**
     * @info is public for PHP 5.3 compatibility, should be private
     */
    public function checkReadability(\ZipArchive $zip, $file)
    {
        if (!is_readable($file)) {
            $zip->unchangeAll();
            $zip->close();

            throw new InvalidArgumentException(sprintf('could not read %s', $file));
        }
    }

    /**
     * @info is public for PHP 5.3 compatibility, should be private
     */
    public function addFileToZip(\ZipArchive $zip, $file)
    {
        if (!$zip->addFile($file)) {
            $zip->unchangeAll();
            $zip->close();

            throw new RuntimeException(sprintf('unable to add %s to the zip file', $file));
        }
    }

    /**
     * @info is public for PHP 5.3 compatibility, should be private
     */
    public function addEmptyDir(\ZipArchive $zip, $dir)
    {
        if (!$zip->addEmptyDir($dir)) {
            $zip->unchangeAll();
            $zip->close();

            throw new RuntimeException(sprintf('unable to add %s to the zip file', $dir));
        }
    }

    /**
     * Flushes changes to the archive
     *
     * @param \ZipArchive $zip
     */
    private function flush(\ZipArchive $zip) // flush changes by reopening the file
    {
        $path = $zip->filename;
        $zip->close();
        $zip->open($path, \ZipArchive::CHECKCONS);
    }
}
