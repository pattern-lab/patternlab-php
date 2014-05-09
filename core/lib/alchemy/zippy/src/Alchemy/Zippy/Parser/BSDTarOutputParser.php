<?php

/*
 * This file is part of Zippy.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Zippy\Parser;

use Alchemy\Zippy\Exception\RuntimeException;

/**
 * This class is responsible of parsing GNUTar command line output
 */
class BSDTarOutputParser implements ParserInterface
{
    const PERMISSIONS   = "([ldrwx-]+)";
    const HARD_LINK     = "(\d+)";
    const OWNER         = "([a-z][-a-z0-9]*)";
    const GROUP         = "([a-z][-a-z0-9]*)";
    const FILESIZE      = "(\d*)";
    const DATE          = "([a-zA-Z0-9]+\s+[a-z0-9]+\s+[a-z0-9:]+)";
    const FILENAME      = "(.*)";

    /**
     * @inheritdoc
     */
    public function parseFileListing($output)
    {
        $lines = array_values(array_filter(explode("\n", $output)));
        $members = array();

        // BSDTar outputs two differents format of date according to the mtime
        // of the member. If the member is younger than six months the year is not shown.
        // On 4.5+ FreeBSD system the day is displayed first
        $dateFormats = array('M d Y', 'M d H:i', 'd M Y', 'd M H:i');

        foreach ($lines as $line) {
            $matches = array();

            // drw-rw-r--  0 toto titi     0 Jan  3  1980 practice/
            // -rw-rw-r--  0 toto titi     10240 Jan 22 13:31 practice/records
            if (!preg_match_all("#" .
                self::PERMISSIONS   . "\s+" .   // match (drw-r--r--)
                self::HARD_LINK     . "\s+" .   // match (1)
                self::OWNER         . "\s" .    // match (toto)
                self::GROUP         . "\s+" .   // match (titi)
                self::FILESIZE      . "\s+" .   // match (0)
                self::DATE          . "\s+" .   // match (Jan  3  1980)
                self::FILENAME      .           // match (practice)
                "#", $line, $matches, PREG_SET_ORDER
            )) {
                continue;
            }

            $chunks = array_shift($matches);

            if (8 !== count($chunks)) {
                continue;
            }

            $date = null;

            foreach ($dateFormats as $format) {
                $date = \DateTime::createFromFormat($format, $chunks[6]);

                if (false === $date) {
                    continue;
                } else {
                    break;
                }
            }

            if (false === $date) {
                throw new RuntimeException(sprintf('Failed to parse mtime date from %s', $line));
            }

             $members[] = array(
                'location'  => $chunks[7],
                'size'      => $chunks[5],
                'mtime'     => $date,
                'is_dir'    => 'd' === $chunks[1][0]
            );
        }

        return $members;
    }

    /**
     * @inheritdoc
     */
    public function parseInflatorVersion($output)
    {
        $chunks = explode(' ', $output, 3);

        if (2 > count($chunks)) {
            return null;
        }

        list($name, $version) = explode(' ', $output, 3);

        return $version;
    }

    /**
     * @inheritdoc
     */
    public function parseDeflatorVersion($output)
    {
        return $this->parseInflatoVersion($output);
    }
}
