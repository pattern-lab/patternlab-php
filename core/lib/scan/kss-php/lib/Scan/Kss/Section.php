<?php

/**
 * Section
 *
 * A KSS Comment Block that represents a single section containing a description,
 * modifiers, and a section reference.
 */

namespace Scan\Kss;

class Section
{
    /**
     * The raw KSS Comment Block before it was chopped into pieces
     *
     * @var string
     */
    protected $rawComment = '';

    /**
     * The sections of the KSS Comment Block
     *
     * @var array
     */
    protected $commentSections = array();

    /**
     * The file where the KSS Comment Block came from
     *
     * @var \SplFileObject
     */
    protected $file = null;

    /**
     * The parsed markup comment in the KSS Block
     *
     * @var string
     */
    protected $markup = null;

    /**
     * The deprecation notice in the KSS Block
     *
     * @var string
     */
    protected $deprecated = null;

    /**
     * The experimental notice in the KSS Block
     *
     * @var string
     */
    protected $experimental = null;

    /**
     * The section reference identifier
     *
     * @var string
     */
    protected $reference = null;

    /**
     * Creates a section with the KSS Comment Block and source file
     *
     * @param string $comment
     * @param \SplFileObject $file
     */
    public function __construct($comment = '', \SplFileObject $file = null)
    {
        $this->rawComment = $comment;
        $this->file = $file;
    }

    /**
     * Returns the source filename for where the comment block was located
     *
     * @return string
     */
    public function getFilename()
    {
        if ($this->file === null) {
            return '';
        }

        return $this->file->getFilename();
    }

    /**
     * Returns the title of the section
     *
     * @return string
     */
    public function getTitle()
    {
        $title = '';

        $titleComment = $this->getTitleComment();
        if (preg_match('/^\s*#+\s*(.+)/', $titleComment, $matches)) {
            $title = $matches[1];
        }

        return $title;
    }

    /**
     * Returns the description for the section
     *
     * @return string
     */
    public function getDescription()
    {
        $descriptionSections = array();

        foreach ($this->getCommentSections() as $commentSection) {
            // Anything that is not the section comment or modifiers comment
            // must be the description comment
            if ($commentSection != $this->getReferenceComment()
                && $commentSection != $this->getTitleComment()
                && $commentSection != $this->getMarkupComment()
                && $commentSection != $this->getDeprecatedComment()
                && $commentSection != $this->getExperimentalComment()
                && $commentSection != $this->getModifiersComment()
            ) {
                $descriptionSections[] = $commentSection;
            }
        }

        return implode("\n\n", $descriptionSections);
    }

    /**
     * Returns the markup defined in the section
     *
     * @return string
     */
    public function getMarkup()
    {
        if ($this->markup === null) {
            if ($markupComment = $this->getMarkupComment()) {
                $this->markup = trim(preg_replace('/^\s*Markup:/i', '', $markupComment));
            }
        }

        return $this->markup;
    }

    /**
     * Returns the markup for the normal element (without modifierclass)
     *
     * @param string $replacement Replacement for $modifierClass variable
     * @return void
     */
    public function getMarkupNormal($replacement = '')
    {
        return str_replace('$modifierClass', $replacement, $this->getMarkup());
    }

    /**
     * Returns the deprecation notice defined in the section
     *
     * @return string
     */
    public function getDeprecated()
    {
        if ($this->deprecated === null) {
            if ($deprecatedComment = $this->getDeprecatedComment()) {
                $this->deprecated = trim(preg_replace('/^\s*Deprecated:/i', '', $deprecatedComment));
            }
        }

        return $this->deprecated;
    }

    /**
     * Returns the experimental notice defined in the section
     *
     * @return string
     */
    public function getExperimental()
    {
        if ($this->experimental === null) {
            if ($experimentalComment = $this->getExperimentalComment()) {
                $this->experimental = trim(preg_replace('/^\s*Experimental:/i', '', $experimentalComment));
            }
        }

        return $this->experimental;
    }

    /**
     * Returns the modifiers used in the section
     *
     * @return array
     */
    public function getModifiers()
    {
        $lastIndent = null;
        $modifiers = array();

        if ($modiferComment = $this->getModifiersComment()) {
            $modifierLines = explode("\n", $modiferComment);
            foreach ($modifierLines as $line) {
                if (empty($line)) {
                    continue;
                }

                preg_match('/^\s*/', $line, $matches);
                $indent = strlen($matches[0]);

                if ($lastIndent && $indent > $lastIndent) {
                    $modifier = end($modifiers);
                    $modifier->setDescription($modifier->getDescription() + trim($line));
                } else {
                    $lineParts = explode(' - ', $line);

                    $name = trim(array_shift($lineParts));

                    $description = '';
                    if (!empty($lineParts)) {
                        $description = trim(implode(' - ', $lineParts));
                    }
                    $modifier = new Modifier($name, $description);

                    // If the CSS has a markup, pass it to the modifier for the example HTML
                    if ($markup = $this->getMarkup()) {
                        $modifier->setMarkup($markup);
                    }
                    $modifiers[] = $modifier;
                }
            }
        }

        return $modifiers;
    }

    /**
     * Returns the reference number for the section
     *
     * @return string
     *
     * @deprecated Method deprecated in v0.3.1
     */
    public function getSection()
    {
        return $this->getReference();
    }

    /**
     * Returns the reference number for the section
     *
     * @param boolean $trimmed OPTIONAL
     *
     * @return string
     */
    public function getReference($trimmed = false)
    {
        if ($this->reference === null) {
            $referenceComment = $this->getReferenceComment();
            $referenceComment = preg_replace('/\.$/', '', $referenceComment);

            if (preg_match('/Pattern (\S*)/', $referenceComment, $matches)) {
                $this->reference = $matches[1];
            }
        }

        return ($trimmed && $this->reference !== null)
            ? self::trimReference($this->reference)
            : $this->reference;
    }

    /**
     * Trims off all trailing zeros and periods on a reference
     *
     * @param string $reference
     *
     * @return string
     */
    public static function trimReference($reference)
    {
        if (substr($reference, -1) == '.') {
            $reference = substr($reference, 0, -1);
        }
        while (preg_match('/(\.0+)$/', $reference, $matches)) {
            $reference = substr($reference, 0, strlen($matches[1]) * -1);
        }
        return $reference;
    }

    /**
     * Checks to see if a section belongs to a specified reference
     *
     * @param string $reference
     *
     * @return boolean
     */
    public function belongsToReference($reference)
    {
        $reference = self::trimReference($reference);
        return strpos($this->getReference() . '.', $reference . '.') === 0;
    }

    /**
     * Helper method for calculating the depth of the instantiated section
     *
     * @return int
     */
    public function getDepth()
    {
        return self::calcDepth($this->getReference());
    }

    /**
     * Calculates and returns the depth of a section reference
     *
     * @param string $reference
     *
     * @return int
     */
    public static function calcDepth($reference)
    {
        $reference = self::trimReference($reference);
        return substr_count($reference, '.');
    }

    /**
     * Helper method for calculating the score of the instantiated section
     *
     * @return int
     */
    public function getDepthScore()
    {
        return self::calcDepthScore($this->getReference());
    }
    /**
     * Calculates and returns the depth score for the section. Useful for sorting
     * sections correctly by their section reference numbers
     *
     * @return int
     */
    public static function calcDepthScore($reference)
    {
        $reference = self::trimReference($reference);
        $sectionParts = explode('.', $reference);
        $score = 0;
        foreach ($sectionParts as $level => $part) {
            $score += $part * (1 / pow(10, $level));
        }
        return $score;
    }

    /**
     * Function to help sort sections by depth and then depth score
     *
     * @param Section $a
     * @param Section $b
     *
     * @return int
     */
    public static function depthSort(Section $a, Section $b)
    {
        if ($a->getDepth() == $b->getDepth()) {
            return self::depthScoreSort($a, $b);
        }
        return $a->getDepth() > $b->getDepth();
    }

    /**
     * Function to help sort sections by their depth score
     *
     * @param Section $a
     * @param Section $b
     *
     * @return int
     */
    public static function depthScoreSort(Section $a, Section $b)
    {
        return $a->getDepthScore() > $b->getDepthScore();
    }

    /**
     * Returns the comment block used when creating the section as an array of
     * paragraphs within the comment block
     *
     * @return array
     */
    protected function getCommentSections()
    {
        if (empty($this->commentSections) && $this->rawComment) {
            $this->commentSections = explode("\n\n", $this->rawComment);
        }

        return $this->commentSections;
    }

    /**
     * Gets the title part of the KSS Comment Block
     *
     * @return string
     */
    protected function getTitleComment()
    {
        $titleComment = null;

        foreach ($this->getCommentSections() as $commentSection) {
            // Identify the title by the # markdown header syntax
            if (preg_match('/^\s*#/i', $commentSection)) {
                $titleComment = $commentSection;
                break;
            }
        }

        return $titleComment;
    }

    /**
     * Returns the part of the KSS Comment Block that contains the markup
     *
     * @return string
     */
    protected function getMarkupComment()
    {
        $markupComment = null;

        foreach ($this->getCommentSections() as $commentSection) {
            // Identify the markup comment by the Markup: marker
            if (preg_match('/^\s*Markup:/i', $commentSection)) {
                $markupComment = $commentSection;
                break;
            }
        }

        return $markupComment;
    }

    /**
     * Returns the part of the KSS Comment Block that contains the deprecated
     * notice
     *
     * @return string
     */
    protected function getDeprecatedComment()
    {
        $deprecatedComment = null;

        foreach ($this->getCommentSections() as $commentSection) {
            // Identify the deprecation notice by the Deprecated: marker
            if (preg_match('/^\s*Deprecated:/i', $commentSection)) {
                $deprecatedComment = $commentSection;
                break;
            }
        }

        return $deprecatedComment;
    }

    /**
     * Returns the part of the KSS Comment Block that contains the experimental
     * notice
     *
     * @return string
     */
    protected function getExperimentalComment()
    {
        $experimentalComment = null;

        foreach ($this->getCommentSections() as $commentSection) {
            // Identify the experimental notice by the Experimental: marker
            if (preg_match('/^\s*Experimental:/i', $commentSection)) {
                $experimentalComment = $commentSection;
                break;
            }
        }

        return $experimentalComment;
    }

    /**
     * Gets the part of the KSS Comment Block that contains the section reference
     *
     * @return string
     */
    protected function getReferenceComment()
    {
        $referenceComment = null;

        foreach ($this->getCommentSections() as $commentSection) {
            // Identify it by the Styleguide 1.2.3. pattern
            if (preg_match('/Pattern \S/i', $commentSection)) {
                $referenceComment = $commentSection;
                break;
            }
        }

        return $referenceComment;
    }

    /**
     * Returns the part of the KSS Comment Block that contains the modifiers
     *
     * @return string
     */
    protected function getModifiersComment()
    {
        $modifiersComment = null;

        foreach ($this->getCommentSections() as $commentSection) {
            // Assume that the modifiers section starts with either a class or a
            // pseudo class
            if (preg_match('/^\s*(?:\.|:)/', $commentSection)) {
                $modifiersComment = $commentSection;
                break;
            }
        }

        return $modifiersComment;
    }
}
