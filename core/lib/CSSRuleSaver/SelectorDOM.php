<?php

namespace CSSRuleSaver;

/**
 * SelectorDOM.
 * (c) Copyright TJ Holowaychuk <tj@vision-media.ca> MIT Licensed
 *
 * Persitant object for selecting elements.
 *
 *   $dom = new SelectorDOM($html);
 *   $links = $dom->select('a');
 *   $list_links = $dom->select('ul li a');
 *
 */
class SelectorDOM {

    const VERSION = '1.1.3';

    /**
     * @var DOMXPath
     */
    protected $xpath;

    /**
     * Map of regexes to convert CSS selector to XPath
     *
     * @var array
     */
    public static $regexMap = array(
        '/\s*,\s*/' => '|descendant-or-self::',
        '/:(button|submit|file|checkbox|radio|image|reset|text|password)/' => 'input[@type="\1"]',
        '/\[(\w+)\]/' => '*[@\1]', # [id]
        '/\[(\w+)=[\'"]?(.*?)[\'"]?\]/' => '[@\1="\2"]', # foo[id=foo]
        '/^\[/' => '*[', # [id=foo]
        '/([\w\-]+)\#([\w\-]+)/' => '\1[@id="\2"]', # div#foo
        '/\#([\w\-]+)/' => '*[@id="\1"]', # #foo
        '/([\w\-]+)\.([\w\-]+)/' => '\1[contains(concat(" ",@class," ")," \2 ")]', # div.foo
        '/\.([\w\-]+)/' => '*[contains(concat(" ",@class," ")," \1 ")]', # .foo
        '/([\w\-]+):first-child/' => '*/\1[position()=1]',
        '/([\w\-]+):last-child/' => '*/\1[position()=last()]',
        '/:first-child/' => '*/*[position()=1]',
        '/:last-child/' => '*/*[position()=last()]',
        '/([\w\-]+):nth-child\((\d+)\)/' => '*/\1[position()=\2]',
        '/:nth-child\((\d+)\)/' => '*/*[position()=\1]',
        '/([\w\-]+):contains\((.*?)\)/' => '\1[contains(string(.),"\2")]',
        '/\s*>\s*/' => '/', # >
        '/\s*~\s*/' => '/following-sibling::', # ~
        '/\s*\+\s*([\w\-]+)/' => '/following-sibling::\1[position()=1]', # +
        '/\]\*/' => ']',
        '/\]\/\*/' => ']',
    );

    /**
     * Load $data into the object
     *
     * @param string|DOMDocument $data
     * @param array $errors A by-ref capture for libxml error messages.
     */
    public function __construct($data, &$errors = null) {
        # Wrap this with libxml errors off
        # this both sets the new value, and returns the previous.
        $lib_xml_errors = libxml_use_internal_errors(true);

        if (is_a($data, 'DOMDocument')) {
            $this->xpath = new \DOMXpath($data);
        } else {
            $dom = new \DOMDocument();
            $dom->loadHTML($data);
            $this->xpath = new \DOMXpath($dom);
        }

        # Clear any errors and restore the original value
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($lib_xml_errors);
    }

    /**
     * Select elements from the loaded HTML using the css $selector.
     * When $as_array is true elements and their children will
     * be converted to array's containing the following keys (defaults to true):
     *
     *  - name : element name
     *  - text : element text
     *  - children : array of children elements
     *  - attributes : attributes array
     *
     * Otherwise regular DOMElement's will be returned.
     *
     * @param string $selector CSS Selector
     * @param boolean $as_array Whether to return an array or DOMElement
     */
    public function select($selector, $as_array = true) {
        $elements = $this->xpath->evaluate(self::selectorToXpath($selector));
        return $as_array ? self::elementsToArray($elements) : $elements;
    }

    /**
     * This allows a static access to the class, in the same way as the
     * `select_elements` function did.
     *
     * @see $this->select()
     * @param string $html
     * @param string $selector CSS Selector
     */
    public static function selectElements($selector, $html, $as_array = true) {
		$dom = new SelectorDOM($html);
		return $dom->select($selector, $as_array);
    }

    /**
     * Convert $elements to an array.
     *
     * @param DOMNodeList $elements
     */
    public function elementsToArray($elements) {
        $array = array();
        for ($i = 0, $length = $elements->length; $i < $length; ++$i) {
            if ($elements->item($i)->nodeType == XML_ELEMENT_NODE) {
                array_push($array, self::elementToArray($elements->item($i)));
            }
        }
        return $array;
    }

    /**
     * Convert $element to an array.
     */
    public function elementToArray($element) {
        $array = array(
            'name'       => $element->nodeName,
            'attributes' => array(),
            'text'       => $element->textContent,
            'children'   => self::elementsToArray($element->childNodes),
        );
        if ($element->attributes->length) {
            foreach($element->attributes as $key => $attr) {
                $array['attributes'][$key] = $attr->value;
            }
        }
        return $array;
    }

    /**
     * Convert $selector into an XPath string.
     */
    public static function selectorToXpath($selector) {
        // remove spaces around operators
        $selector = preg_replace('/\s*(>|~|\+|,)\s*/', '$1', $selector);
        $selectors = preg_split("/\s+/", $selector);
        // Process all regular expressions to convert selector to XPath
        foreach ($selectors as &$selector) {
            foreach (self::$regexMap as $regex => $replacement) {
                $selector = preg_replace($regex, $replacement, $selector);
            }
        }
        $selector = implode('/descendant::', $selectors);
        $selector = 'descendant-or-self::' . $selector;
        return $selector;
    }

}

#
# Procedural components
#

define('SELECTOR_VERSION', SelectorDOM::VERSION);

/**
 * Provides a procedural function to select use SelectorDOM::select()
 * on some HTML.
 */
function select_elements($selector, $html, $as_array = true) {
    return SelectorDOM::selectElements($selector, $html, $as_array);
}

