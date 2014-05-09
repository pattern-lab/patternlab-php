<?php

/*
 * This file is part of Zippy.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Zippy\Resource;

use Alchemy\Zippy\Exception\InvalidArgumentException;
use Doctrine\Common\Collections\ArrayCollection;

class ResourceCollection extends ArrayCollection
{
    private $context;
    private $temporary;

    /**
     * Constructor
     *
     * @param String     $context
     * @param Resource[] $elements An array of Resource
     */
    public function __construct($context, array $elements, $temporary)
    {
        array_walk($elements, function ($element) {
            if (!$element instanceof Resource) {
                throw new InvalidArgumentException('ResourceCollection only accept Resource elements');
            }
        });

        $this->context = $context;
        $this->temporary = (Boolean) $temporary;
        parent::__construct($elements);
    }

    /**
     * Returns the context related to the collection
     *
     * @return String
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Tells whether the collection is temporary or not.
     *
     * A ResourceCollection is temporary when it required a temporary folder to
     * fetch data
     *
     * @return type
     */
    public function isTemporary()
    {
        return $this->temporary;
    }

    /**
     * Returns true if all resources can be processed in place, false otherwise
     *
     * @return Boolean
     */
    public function canBeProcessedInPlace()
    {
        if (count($this) === 1) {
            if (null !== $context = $this->first()->getContextForProcessInSinglePlace()) {
                $this->context = $context;
                return true;
            }
        }

        foreach ($this as $resource) {
            if (!$resource->canBeProcessedInPlace($this->context)) {
                return false;
            }
        }

        return true;
    }
}
