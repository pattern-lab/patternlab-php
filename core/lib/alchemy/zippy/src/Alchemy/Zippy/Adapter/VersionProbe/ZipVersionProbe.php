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

namespace Alchemy\Zippy\Adapter\VersionProbe;

use Alchemy\Zippy\ProcessBuilder\ProcessBuilderFactoryInterface;

class ZipVersionProbe implements VersionProbeInterface
{
    private $isSupported;
    private $inflator;
    private $deflator;

    public function __construct(ProcessBuilderFactoryInterface $inflator, ProcessBuilderFactoryInterface $deflator)
    {
        $this->inflator = $inflator;
        $this->deflator = $deflator;
    }

    /**
     * Set the inflator to zip
     *
     * @param  ProcessBuilderFactoryInterface $inflator
     * @return ZipVersionProbe
     */
    public function setInflator(ProcessBuilderFactoryInterface $inflator)
    {
        $this->inflator = $inflator;

        return $this;
    }

    /**
     * Set the deflator to unzip
     *
     * @param  ProcessBuilderFactoryInterface $deflator
     * @return ZipVersionProbe
     */
    public function setDeflator(ProcessBuilderFactoryInterface $deflator)
    {
        $this->deflator = $deflator;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus()
    {
        if (null !== $this->isSupported) {
            return $this->isSupported;
        }

        if (null === $this->inflator || null === $this->deflator) {
            return $this->isSupported = VersionProbeInterface::PROBE_NOTSUPPORTED;
        }

        $processDeflate = $this
            ->deflator
            ->create()
            ->add('-h')
            ->getProcess();

        $processDeflate->run();

        $processInflate = $this
            ->inflator
            ->create()
            ->add('-h')
            ->getProcess();

        $processInflate->run();

        if (false === $processDeflate->isSuccessful() || false === $processInflate->isSuccessful()) {
            return $this->isSupported = VersionProbeInterface::PROBE_NOTSUPPORTED;
        }

        $lines = explode("\n", $processInflate->getOutput(), 2);
        $inflatorOk = false !== stripos($lines[0], 'Info-ZIP');

        $lines = explode("\n", $processDeflate->getOutput(), 2);
        $deflatorOk = false !== stripos($lines[0], 'Info-ZIP');

        return $this->isSupported = ($inflatorOk && $deflatorOk) ? VersionProbeInterface::PROBE_OK : VersionProbeInterface::PROBE_NOTSUPPORTED;
    }
}
