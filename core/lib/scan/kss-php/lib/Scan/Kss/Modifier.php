<?php

/**
 * Modifier
 *
 * Object to represent the modifiers section of a KSS Comment Block
 */

namespace Scan\Kss;

class Modifier
{
    /**
     * Name of the modifier
     *
     * @var string
     */
    protected $name = '';

    /**
     * Description of the modifier
     *
     * @var string
     */
    protected $description = '';

    /**
     * Optional markup for the modifier
     *
     * @var string
     */
    protected $markup = null;

    /**
     * Class that this modifier extends from
     *
     * @var string
     */
    protected $extendedClass = null;

    /**
     * Creates a new modifier by adding a name and a description
     *
     * @param string $name
     * @param string $description
     */
    public function __construct($name, $description = '')
    {
        $this->setName($name);
        $this->setDescription($description);
    }

    /**
     * Returns the name of the modifier
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the name of the modifier
     *
     * @param string $name
     */
    public function setName($name)
    {
        $name = $this->parseExtend($name);
        $this->name = $name;
    }

    /**
     * Returns the description of the modifier
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets the description of the modifier
     *
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Returns the markup of the modifier
     *
     * @return string
     */
    public function getMarkup()
    {
        return $this->markup;
    }

    /**
     * Sets the markup of the modifier
     *
     * @param string $markup
     */
    public function setMarkup($markup)
    {
        $this->markup = $markup;
    }

    /**
     * Checks the name for any extend notations and parses that information
     * off and stores it in the $this->extendedClass
     *
     * @param string $name
     *
     * @return $name
     */
    protected function parseExtend($name)
    {
        $this->setExtendedClass(null);

        $nameParts = explode('@extend', $name);
        $name = trim($nameParts[0]);
        if (count($nameParts) > 1) {
            $this->setExtendedClass($nameParts[1]);
        }

        return $name;
    }

    /**
     * Returns whether the modifier is applied by extension
     *
     * @return boolean
     */
    public function isExtender()
    {
        return (bool) $this->getExtendedClass();
    }

    /**
     * Returns the extended class
     *
     * @return string
     */
    public function getExtendedClass()
    {
        return $this->extendedClass;
    }

    /**
     * Sets the extended class. If the class name is empty, assuming null instead
     * and stop further parsing
     *
     * @param string $class
     */
    public function setExtendedClass($class)
    {
        if (empty($class)) {
            $this->extenderClass = null;
            return;
        }

        $this->extendedClass = trim($class);
    }

    /**
     * Returns the class name for the extended class
     *
     * @return string
     */
    public function getExtendedClassName()
    {
        if ($this->getExtendedClass() === null) {
            return '';
        }

        $name = str_replace('%', ' ', $this->getExtendedClass());
        $name = str_replace('.', ' ', $name);
        $name = str_replace(':', ' pseudo-class-', $name);
        return trim($name);
    }

    /**
     * Returns the class name for the modifier
     *
     * @return string
     */
    public function getClassName()
    {
        $name = str_replace('.', ' ', $this->name);
        $name = str_replace(':', ' pseudo-class-', $name);
        return trim($name);
    }

    /**
     * Returns a string of specified html with inserted class names in the correct
     * places for modifiers and extenders.
     *
     * @param string $html OPTIONAL
     *
     * @return string $html
     */
    public function getExampleHtml($html = null)
    {
        if ($html === null) {
            if ($this->getMarkup() === null) {
                return '';
            }
            $html = $this->getMarkup();
        }

        if ($this->isExtender()) {
            $html = str_replace('$modifierClass', '', $html);

            // Use a positive lookbehind and lookahead to ensure we don't 
            // replace anything more than just the targeted class name
            // for example an element name that is the same as the extended 
            // class name (e.g. button)
            $pattern = sprintf('/(?<="| )%s(?="| )/', $this->getExtendedClassName());
            $html = preg_replace(
                $pattern,
                $this->getClassName(),
                $html
            );
        }

        $html = str_replace('$modifierClass', $this->getClassName(), $html);

        return $html;
    }
}
