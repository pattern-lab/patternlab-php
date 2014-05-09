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

use Alchemy\Zippy\Adapter\Resource\ResourceInterface;
use Alchemy\Zippy\Archive\Archive;
use Alchemy\Zippy\Exception\InvalidArgumentException;
use Alchemy\Zippy\Exception\RuntimeException;
use Alchemy\Zippy\Resource\Resource;
use Alchemy\Zippy\Archive\Member;
use Symfony\Component\Process\Exception\ExceptionInterface as ProcessException;

abstract class AbstractTarAdapter extends AbstractBinaryAdapter
{
    /**
     * @inheritdoc
     */
    protected function doCreate($path, $files, $recursive)
    {
        return $this->doTarCreate($this->getLocalOptions(), $path, $files, $recursive);
    }

    /**
     * @inheritdoc
     */
    protected function doListMembers(ResourceInterface $resource)
    {
        return $this->doTarListMembers($this->getLocalOptions(), $resource);
    }

    /**
     * @inheritdoc
     */
    protected function doAdd(ResourceInterface $resource, $files, $recursive)
    {
        return $this->doTarAdd($this->getLocalOptions(), $resource, $files, $recursive);
    }

    /**
     * @inheritdoc
     */
    protected function doRemove(ResourceInterface $resource, $files)
    {
        return $this->doTarRemove($this->getLocalOptions(), $resource, $files);
    }

    /**
     * @inheritdoc
     */
    protected function doExtractMembers(ResourceInterface $resource, $members, $to)
    {
        return $this->doTarExtractMembers($this->getLocalOptions(), $resource, $members, $to);
    }

    /**
     * @inheritdoc
     */
    protected function doExtract(ResourceInterface $resource, $to)
    {
        return $this->doTarExtract($this->getLocalOptions(), $resource, $to);
    }

    /**
     * @inheritdoc
     */
    protected function doGetInflatorVersion()
    {
        $process = $this
            ->inflator
            ->create()
            ->add('--version')
            ->getProcess();

        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException(sprintf(
                'Unable to execute the following command %s {output: %s}',
                $process->getCommandLine(), $process->getErrorOutput()
            ));
        }

        return $this->parser->parseInflatorVersion($process->getOutput() ? : '');
    }

    /**
     * @inheritdoc
     */
    protected function doGetDeflatorVersion()
    {
        return $this->getInflatorVersion();
    }

    protected function doTarCreate($options, $path, $files = null, $recursive = true)
    {
        $files = (array) $files;

        $builder = $this
            ->inflator
            ->create();

        if (!$recursive) {
            $builder->add('--no-recursion');
        }

        $builder->add('--create');

        foreach ((array) $options as $option) {
            $builder->add((string) $option);
        }

        if (0 === count($files)) {
            $nullFile = defined('PHP_WINDOWS_VERSION_BUILD') ? 'NUL' : '/dev/null';

            $builder->add('-');
            $builder->add(sprintf('--files-from %s', $nullFile));
            $builder->add(sprintf('> %s', $path));

            $process = $builder->getProcess();
            $process->run();

        } else {

            $builder->add(sprintf('--file=%s', $path));

            if (!$recursive) {
                $builder->add('--no-recursion');
            }

            $collection = $this->manager->handle(getcwd(), $files);

            $builder->setWorkingDirectory($collection->getContext());

            $collection->forAll(function ($i, Resource $resource) use ($builder) {
                return $builder->add($resource->getTarget());
            });

            $process = $builder->getProcess();

            try {
                $process->run();
            } catch (ProcessException $e) {
                $this->manager->cleanup($collection);
                throw $e;
            }

            $this->manager->cleanup($collection);
        }

        if (!$process->isSuccessful()) {
            throw new RuntimeException(sprintf(
                'Unable to execute the following command %s {output: %s}',
                $process->getCommandLine(),
                $process->getErrorOutput()
            ));
        }

        return new Archive($this->createResource($path), $this, $this->manager);
    }

    protected function doTarListMembers($options, ResourceInterface $resource)
    {
        $builder = $this
            ->inflator
            ->create();

        foreach ($this->getListMembersOptions() as $option) {
            $builder->add($option);
        }

        $builder
            ->add('--list')
            ->add('-v')
            ->add(sprintf('--file=%s', $resource->getResource()));

        foreach ((array) $options as $option) {
            $builder->add((string) $option);
        }

        $process = $builder->getProcess();
        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException(sprintf(
                'Unable to execute the following command %s {output: %s}',
                $process->getCommandLine(),
                $process->getErrorOutput()
            ));
        }

        $members = array();

        foreach ($this->parser->parseFileListing($process->getOutput() ? : '') as $member) {
            $members[] = new Member(
                    $resource,
                    $this,
                    $member['location'],
                    $member['size'],
                    $member['mtime'],
                    $member['is_dir']
            );
        }

        return $members;
    }

    protected function doTarAdd($options, ResourceInterface $resource, $files, $recursive = true)
    {
        $files = (array) $files;

        $builder = $this
            ->inflator
            ->create();

        if (!$recursive) {
            $builder->add('--no-recursion');
        }

        $builder
            ->add('--append')
            ->add(sprintf('--file=%s', $resource->getResource()));

        foreach ((array) $options as $option) {
            $builder->add((string) $option);
        }

        // there will be an issue if the file starts with a dash
        // see --add-file=FILE
        $collection = $this->manager->handle(getcwd(), $files);

        $builder->setWorkingDirectory($collection->getContext());

        $collection->forAll(function ($i, Resource $resource) use ($builder) {
            return $builder->add($resource->getTarget());
        });

        $process = $builder->getProcess();

        try {
            $process->run();
        } catch (ProcessException $e) {
            $this->manager->cleanup($collection);
            throw $e;
        }

        $this->manager->cleanup($collection);

        if (!$process->isSuccessful()) {
            throw new RuntimeException(sprintf(
                'Unable to execute the following command %s {output: %s}',
                $process->getCommandLine(),
                $process->getErrorOutput()
            ));
        }

        return $files;
    }

    protected function doTarRemove($options, ResourceInterface $resource, $files)
    {
        $files = (array) $files;

        $builder = $this
            ->inflator
            ->create();

        $builder
            ->add('--delete')
            ->add(sprintf('--file=%s', $resource->getResource()));

        foreach ((array) $options as $option) {
            $builder->add((string) $option);
        }

        if (!$this->addBuilderFileArgument($files, $builder)) {
            throw new InvalidArgumentException('Invalid files');
        }

        $process = $builder->getProcess();

        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException(sprintf(
                'Unable to execute the following command %s {output: %s}',
                $process->getCommandLine(),
                $process->getErrorOutput()
            ));
        }

        return $files;
    }

    protected function doTarExtract($options, ResourceInterface $resource, $to = null)
    {
        if (null !== $to && !is_dir($to)) {
            throw new InvalidArgumentException(sprintf("%s is not a directory", $to));
        }

        $builder = $this
            ->inflator
            ->create();

        $builder
            ->add('--extract')
            ->add(sprintf('--file=%s', $resource->getResource()));

        foreach ($this->getExtractOptions() as $option) {
            $builder
                ->add($option);
        }

        foreach ((array) $options as $option) {
            $builder->add((string) $option);
        }

        if (null !== $to) {
            $builder
                ->add('--directory')
                ->add($to);
        }

        $process = $builder->getProcess();

        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException(sprintf(
                'Unable to execute the following command %s {output: %s}',
                $process->getCommandLine(),
                $process->getErrorOutput()
            ));
        }

        return new \SplFileInfo($to ? : $resource->getResource());
    }

    protected function doTarExtractMembers($options, ResourceInterface $resource, $members, $to = null)
    {
        if (null !== $to && !is_dir($to)) {
            throw new InvalidArgumentException(sprintf("%s is not a directory", $to));
        }

        $members = (array) $members;

        $builder = $this
            ->inflator
            ->create();

        $builder
            ->add('--extract')
            ->add(sprintf('--file=%s', $resource->getResource()));

        foreach ($this->getExtractMembersOptions() as $option) {
            $builder
                ->add($option);
        }

        foreach ((array) $options as $option) {
            $builder->add((string) $option);
        }

        if (null !== $to) {
            $builder
                ->add('--directory')
                ->add($to);
        }

        if (!$this->addBuilderFileArgument($members, $builder)) {
            throw new InvalidArgumentException('Invalid files');
        }

        $process = $builder->getProcess();

        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException(sprintf(
                'Unable to execute the following command %s {output: %s}',
                $process->getCommandLine(),
                $process->getErrorOutput()
            ));
        }

        return $members;
    }

    /**
     * Returns an array of option for the listMembers command
     *
     * @return Array
     */
    abstract protected function getListMembersOptions();

    /**
     * Returns an array of option for the extract command
     *
     * @return Array
     */
    abstract protected function getExtractOptions();

    /**
     * Returns an array of option for the extractMembers command
     *
     * @return Array
     */
    abstract protected function getExtractMembersOptions();

    /**
     * Gets adapter specific additional options
     *
     * @return Array
     */
    abstract protected function getLocalOptions();
}
