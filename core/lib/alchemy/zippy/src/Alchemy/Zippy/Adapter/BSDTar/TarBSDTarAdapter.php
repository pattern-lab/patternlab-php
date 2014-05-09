<?php

namespace Alchemy\Zippy\Adapter\BSDTar;

use Alchemy\Zippy\Adapter\AbstractTarAdapter;
use Alchemy\Zippy\Adapter\VersionProbe\BSDTarVersionProbe;
use Alchemy\Zippy\Parser\ParserInterface;
use Alchemy\Zippy\Resource\ResourceManager;
use Alchemy\Zippy\ProcessBuilder\ProcessBuilderFactoryInterface;

/**
 * BSDTAR allows you to create and extract files from archives using BSD tar
 *
 * @see http://people.freebsd.org/~kientzle/libarchive/man/bsdtar.1.txt
 */
class TarBSDTarAdapter extends AbstractTarAdapter
{
    public function __construct(ParserInterface $parser, ResourceManager $manager, ProcessBuilderFactoryInterface $inflator, ProcessBuilderFactoryInterface $deflator)
    {
        parent::__construct($parser, $manager, $inflator, $deflator);
        $this->probe = new BSDTarVersionProbe($inflator, $deflator);
    }

    /**
     * @inheritdoc
     */
    protected function getLocalOptions()
    {
        return array();
    }

    /**
     * @inheritdoc
     */
    public static function getName()
    {
        return 'bsd-tar';
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultDeflatorBinaryName()
    {
        return array('bsdtar', 'tar');
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultInflatorBinaryName()
    {
        return array('bsdtar', 'tar');
    }

    /**
     * {@inheritdoc}
     */
    protected function getListMembersOptions()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtractOptions()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtractMembersOptions()
    {
        return array();
    }
}
