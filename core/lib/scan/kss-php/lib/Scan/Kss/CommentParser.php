<?php

/**
 * CommentParser
 *
 * Searches a file for all single line and multi-line comments and stores them
 * in the object for later use.
 */

namespace Scan\Kss;

class CommentParser
{
    /**
     * File being parsed
     *
     * @var \SplFileObject
     */
    protected $file = null;

    /**
     * Options use to control the parser
     *
     * @var array
     */
    protected $options = array();

    /**
     * Storage for comment blocks
     *
     * @var array
     */
    protected $blocks = array();

    /**
     * Flag for whether the file has been parsed for comments yet
     *
     * @var boolean
     */
    protected $parsed = false;

    /**
     * Sets up the parser with the file needed and any options to use when parsing
     *
     * @param \SplFileObject $file
     * @param array $options
     */
    public function __construct(\SplFileObject $file, array $options = array())
    {
        $this->file = $file;
        $this->options = $options;
    }

    /**
     * Returns the parsed comment blocks or if object is not yet parsed, parses
     * first and then returns the result
     *
     * @return array
     */
    public function getBlocks()
    {
        if (!$this->parsed) {
            $this->parseBlocks();
        }
        return $this->blocks;
    }

    /**
     * Parses each line of the file looking for single or multi-line comments
     *
     * @return array
     */
    protected function parseBlocks()
    {
        $this->blocks = array();
        $currentBlock = '';
        // Do we need insideSingleLineBlock? It doesn't seem to be used anywhere
        // Original Ruby version of KSS had it but I'm not seeing a purpose to it
        $insideSingleLineBlock = false;
        $insideMultiLineBlock = false;

        foreach ($this->file as $line) {
            $isSingleLineComment = self::isSingleLineComment($line);
            $isStartMultiLineComment = self::isStartMultiLineComment($line);
            $isEndMultiLineComment = self::isEndMultiLineComment($line);

            if ($isSingleLineComment) {
                $parsed = self::parseSingleLineComment($line);

                if ($insideSingleLineBlock) {
                    $currentBlock .= "\n";
                } else {
                    $insideSingleLineBlock = true;
                }

                $currentBlock .= $parsed;
            }

            if ($isStartMultiLineComment || $insideMultiLineBlock) {
                $parsed = self::parseMultiLineComment($line);

                if ($insideMultiLineBlock) {
                    $currentBlock .= "\n";
                } else {
                    $insideMultiLineBlock = true;
                }

                $currentBlock .= $parsed;
            }

            if ($isEndMultiLineComment) {
                $insideMultiLineBlock = false;
            }

            // If we're not in a comment then end the current block and go to
            // the next one
            if (!$isSingleLineComment && !$insideMultiLineBlock) {
                if (!empty($currentBlock)) {
                    $this->blocks[] = $this->normalize($currentBlock);
                    $insideSingleLineBlock = false;
                    $currentBlock = '';
                }
            }
        }

        $this->parsed = true;
        return $this->blocks;
    }

    /**
     * Makes all the white space consistent among the lines in a comment block.
     * That is if the first and second line had 10 spaces but the third line was
     * indented to 15 spaces, we'd normalize it so the first and second line have
     * no spaces and the third line has 5 spaces.
     *
     * @param string $block
     *
     * @return string
     */
    protected function normalize($block)
    {
        // Remove any [whitespace]*'s from the start of each line
        $normalizedBlock = preg_replace('-^\s*\*+-m', '', $block);

        $indentSize = null;
        $blockLines = explode("\n", $normalizedBlock);
        $normalizedLines = array();
        foreach ($blockLines as $line) {
            preg_match('/^\s*/', $line, $matches);
            $precedingWhitespace = strlen($matches[0]);
            if ($indentSize === null) {
                $indentSize = $precedingWhitespace;
            }

            if ($indentSize <= $precedingWhitespace && $indentSize > 0) {
                $line = substr($line, $indentSize);
            }

            $normalizedLines[] = $line;
        }

        return trim(implode("\n", $normalizedLines));
    }

    /**
     * Checks if the comment is a single line comment
     *
     * @param string $line
     *
     * @return boolean
     */
    public static function isSingleLineComment($line)
    {
        return (bool) preg_match('-^\s*//-', $line);
    }

    /**
     * Checks if the line is the start of a multi-line comment
     *
     * @param string $line
     *
     * @return boolean
     */
    public static function isStartMultiLineComment($line)
    {
        return (bool) preg_match('-^\s*/\*-', $line);
    }

    /**
     * Checks if the line is the end of a multi-line comment
     *
     * @param string $line
     *
     * @return boolean
     */
    public static function isEndMultiLineComment($line)
    {
        return (bool) preg_match('-.*\*/-', $line);
    }

    /**
     * Removes the comment markers from a single line comment and trims the line
     *
     * @param string $line
     *
     * @return string
     */
    public static function parseSingleLineComment($line)
    {
        return rtrim(preg_replace('-^\s*//-', '', $line));
    }

    /**
     * Removes the comment markers from a multi line comment and trims the line
     *
     * @param string $line
     *
     * @return string
     */
    public static function parseMultiLineComment($line)
    {
        $parsed = preg_replace('-^\s*/\*+-', '', $line);
        $parsed = preg_replace('-\*/-', '', $parsed);
        return rtrim($parsed);
    }
}
