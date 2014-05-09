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

abstract class AbstractTarVersionProbe implements VersionProbeInterface
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

        $good = true;

        foreach (array($this->inflator, $this->deflator) as $builder) {
            $process = $builder
                ->create()
                ->add('--version')
                ->getProcess();

            $process->run();

            if (!$process->isSuccessful()) {
                return $this->isSupported = VersionProbeInterface::PROBE_NOTSUPPORTED;
            }

            $lines = explode("\n", $process->getOutput(), 2);
            $good = false !== stripos($lines[0], $this->getVersionSignature());

            if (!$good) {
                break;
            }
        }

        $this->isSupported = $good ? VersionProbeInterface::PROBE_OK : VersionProbeInterface::PROBE_NOTSUPPORTED;

        return $this->isSupported;
    }

    /**
     * Returns the signature of inflator/deflator
     *
     * @return string
     */
    abstract protected function getVersionSignature();
}
