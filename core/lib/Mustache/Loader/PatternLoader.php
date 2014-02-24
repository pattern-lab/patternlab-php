<?php

/*
 * PatternLoader has been modified from the FilesystemLoader.
 *
 * FileSytemLoader is (c) 2012 Justin Hileman
 *
 * PatternLoader credits:
 *    Dave Olsen, dmolsen.com
 *    @coding-stuff for the initial pattern parameter code
 */

/**
 * Mustache Template filesystem Loader implementation.
 *
 * A FilesystemLoader instance loads Mustache Template source from the filesystem by name:
 *
 *     $loader = new Mustache_Loader_FilesystemLoader(dirname(__FILE__).'/views');
 *     $tpl = $loader->load('foo'); // equivalent to `file_get_contents(dirname(__FILE__).'/views/foo.mustache');
 *
 * This is probably the most useful Mustache Loader implementation. It can be used for partials and normal Templates:
 *
 *     $m = new Mustache(array(
 *          'loader'          => new Mustache_Loader_FilesystemLoader(dirname(__FILE__).'/views'),
 *          'partials_loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__).'/views/partials'),
 *     ));
 */
class Mustache_Loader_PatternLoader implements Mustache_Loader
{
    private $baseDir;
    private $extension    = '.mustache';
    private $templates    = array();
    private $patternPaths = array();

    /**
     * Mustache filesystem Loader constructor.
     *
     * Passing an $options array allows overriding certain Loader options during instantiation:
     *
     *     $options = array(
     *         // The filename extension used for Mustache templates. Defaults to '.mustache'
     *         'extension' => '.ms',
     *     );
     *
     * @throws Mustache_Exception_RuntimeException if $baseDir does not exist.
     *
     * @param string $baseDir Base directory containing Mustache template files.
     * @param array  $options Array of Loader options (default: array())
     */
    public function __construct($baseDir, array $options = array())
    {
        $this->baseDir = rtrim(realpath($baseDir), '/');

        if (!is_dir($this->baseDir)) {
            throw new Mustache_Exception_RuntimeException(sprintf('FilesystemLoader baseDir must be a directory: %s', $baseDir));
        }

        if (array_key_exists('extension', $options)) {
            if (empty($options['extension'])) {
                $this->extension = '';
            } else {
                $this->extension = '.' . ltrim($options['extension'], '.');
            }
        }

        if (array_key_exists('patternPaths', $options)) {
            $this->patternPaths = $options['patternPaths'];
        }

    }

    /**
     * Load a Template by name.
     *
     *     $loader = new Mustache_Loader_FilesystemLoader(dirname(__FILE__).'/views');
     *     $loader->load('admin/dashboard'); // loads "./views/admin/dashboard.mustache";
     *
     * @param string $name
     *
     * @return string Mustache Template source
     */
    public function load($name)
    {
        if (!isset($this->templates[$name])) {
            try {
                $this->templates[$name] = $this->loadFile($name);
            } catch (Exception $e) {
                print "The partial, ".$name.", wasn't found so a pattern failed to build.\n";
            }
        }
        
        return (isset($this->templates[$name])) ? $this->templates[$name] : false;
        
    }

    /**
     * Helper function for loading a Mustache file by name.
     *
     * @throws Mustache_Exception_UnknownTemplateException If a template file is not found.
     *
     * @param string $name
     *
     * @return string Mustache Template source
     */
    protected function loadFile($name)
    {
        
        // get pattern data
        list($partialName,$styleModifier,$parameters) = $this->getPartialInfo($name);
        
        // get the real file path for the pattern
        $fileName = $this->getFileName($partialName);
        //print $fileName."\n";
        // throw error if path is not found
        if (!file_exists($fileName)) {
            throw new Mustache_Exception_UnknownTemplateException($name);
        }
        
        // get the file data
        $fileData = file_get_contents($fileName);
        
        // if the pattern name had a style modifier find & replace it
        if (count($styleModifier) > 0) {
            $fileData = $this->findReplaceParameters($fileData, $styleModifier);
        }
        
        // if the pattern name had parameters find & replace them
        if (count($parameters) > 0) {
            $fileData = $this->findReplaceParameters($fileData, $parameters);
        }
        
        return $fileData;
        
    }
    
    /**
     * Helper function for getting a Mustache template file name.
     * @param  {String}       the pattern type for the pattern
     * @param  {String}       the pattern sub-type
     *
     * @return {Array}        an array of rendered partials that match the given path
     */
    protected function getFileName($name)
    {
        
        $fileName = "";
        $dirSep   = DIRECTORY_SEPARATOR;
        
        // test to see what kind of path was supplied
        $posDash  = strpos($name,"-");
        $posSlash = strpos($name,$dirSep);
        if (($posSlash === false) && ($posDash !== false)) {
           
           list($patternType,$pattern) = $this->getPatternInfo($name);
           
           // see if the pattern is an exact match for patternPaths. if not iterate over patternPaths to find a likely match
           if (isset($this->patternPaths[$patternType][$pattern])) {
              $fileName = $this->baseDir.$dirSep.$this->patternPaths[$patternType][$pattern]["patternSrcPath"];
           } else if (isset($this->patternPaths[$patternType])) {
              foreach($this->patternPaths[$patternType] as $patternMatchKey=>$patternMatchValue) {
                  $pos = strpos($patternMatchKey,$pattern);
                  if ($pos !== false) {
                      $fileName = $this->baseDir.$dirSep.$patternMatchValue["patternSrcPath"];
                      break;
                  }
              }
           }
           
        } else {
           $fileName = $this->baseDir.$dirSep.$name;
        }
        
        if (substr($fileName, 0 - strlen($this->extension)) !== $this->extension) {
            $fileName .= $this->extension;
        }
        
        return $fileName;
        
    }
    
    /**
     * Helper function to return the parts of a partial name
     * @param  {String}       the name of the partial
     *
     * @return {Array}        the pattern type and the name of the pattern
     */
    private function getPatternInfo($name) 
    {
        
        $patternBits = explode("-",$name);
        
        $i = 1;
        $k = 2;
        $c = count($patternBits);
        $patternType = $patternBits[0];
        while (!isset($this->patternPaths[$patternType]) && ($i < $c)) {
            $patternType .= "-".$patternBits[$i];
            $i++;
            $k++;
        }
        
        $patternBits = explode("-",$name,$k);
        $pattern = $patternBits[count($patternBits)-1];
        
        return array($patternType, $pattern);
        
    }
    
    /**
     * Helper function for finding if a partial name has style modifier or parameters
     * @param  {String}       the pattern name
     *
     * @return {Array}        an array containing just the partial name, a style modifier, and any parameters
     */
    protected function getPartialInfo($partial)
    {
        
        $styleModifier = array();
        $parameters    = array();
        
        if (strpos($partial, "(") !== false) {
            $partialBits      = explode("(",$partial,2);
            $partial          = trim($partialBits[0]);
            $parametersString = substr($partialBits[1],0,(strlen($partialBits[1]) - strlen(strrchr($partialBits[1],")"))));
            $parameters       = $this->parseParameters($parametersString);
        }
        
        if (strpos($partial, ":") !== false) {
            $partialBits      = explode(":",$partial,2);
            $partial          = $partialBits[0];
            $styleModifier    = array("styleModifier" => $partialBits[1]);
        }
        
        return array($partial,$styleModifier,$parameters);
        
    }
    
    /**
     * Helper function to find and replace the given parameters in a particular partial before handing it back to Mustache
     * @param  {String}       the file contents
     * @param  {Array}        an array of paramters to match
     *
     * @return {String}       the modified file contents
     */
    private function findReplaceParameters($fileData, $parameters)
    {
        foreach ($parameters as $k => $v) {
            if ($v == "true") {
               $fileData = preg_replace('/{{\#([\s]*'.$k .'[\s]*)}}(.*?){{\/([\s]*'.$k .'[\s]*)}}/s','$2',$fileData); // {{# asdf }}STUFF{{/ asdf}}
               $fileData = preg_replace('/{{\^([\s]*'.$k .'[\s]*)}}(.*?){{\/([\s]*'.$k .'[\s]*)}}/s','',$fileData); // {{^ asdf }}STUFF{{/ asdf}}
            } else if ($v == "false") {
               $fileData = preg_replace('/{{\^([\s]*'.$k .'[\s]*)}}(.*?){{\/([\s]*'.$k .'[\s]*)}}/s','$2',$fileData); // {{# asdf }}STUFF{{/ asdf}}
               $fileData = preg_replace('/{{\#([\s]*'.$k .'[\s]*)}}(.*?){{\/([\s]*'.$k .'[\s]*)}}/s','',$fileData); // {{^ asdf }}STUFF{{/ asdf}}
            } else {
               $fileData = preg_replace('/{{([\s]*'.$k .'[\s]*)}}/', $v, $fileData); // {{ asdf }}
            }
        }
        return $fileData;
    }
    
    /**
     * Helper function to parse the parameters and return them as an array
     * @param  {String}       tbe parameter string
     *
     * @return {Array}        the keys and values for the parameters
     */
    private function parseParameters($string) 
    {
        
        $parameters     = array();
        $betweenSQuotes = false;
        $betweenDQuotes = false;
        $inKey          = true;
        $inValue        = false;
        $char           = "";
        $buffer         = "";
        $keyBuffer      = "";
        $strLength      = strlen($string);
        
        for ($i = 0; $i < $strLength; $i++) {
            
            $previousChar = $char;
            $char = $string[$i];
            
            if ($inKey && !$betweenDQuotes && !$betweenSQuotes && (($char == "\"") || ($char == "'"))) {
                // if inKey, a quote, and betweenQuotes is false ignore quote, set betweenQuotes to true and empty buffer to kill spaces
                ($char == "\"") ? ($betweenDQuotes = true) : ($betweenSQuotes = true);
            } else if ($inKey && (($betweenDQuotes && ($char == "\"")) || ($betweenSQuotes && ($char == "'"))) && ($previousChar == "\\")) {
                // if inKey, a quote, betweenQuotes is true, and previous character is \ add to buffer
                $buffer   .= $char;
            } else if ($inKey && (($betweenDQuotes && ($char == "\"")) || ($betweenSQuotes && ($char == "'")))) {
                // if inKey, a quote, betweenQuotes is true set betweenQuotes to false, save as key buffer, empty buffer set inKey false
                $keyBuffer = $buffer;
                $buffer    = "";
                $inKey     = false;
                $betweenSQuotes = false;
                $betweenDQuotes = false;
            } else if ($inKey && !$betweenDQuotes && !$betweenSQuotes && ($char == ":")) {
                // if inKey, a colon, betweenQuotes is false, save as key buffer, empty buffer, set inKey false set inValue true
                $keyBuffer = $buffer;
                $buffer    = "";
                $inKey     = false;
                $inValue   = true;
            } else if ($inKey) {
                // if inKey add to buffer
                $buffer   .= $char;
            } else if (!$inKey && !$inValue && ($char == ":")) {
                // if inKey is false, inValue false, and a colon set inValue true
                $inValue = true;
            } else if ($inValue && !$betweenDQuotes && !$betweenSQuotes && (($char == "\"") || ($char == "'"))) {
                // if inValue, a quote, and betweenQuote is false set betweenQuotes to true and empty buffer to kill spaces
                ($char == "\"") ? ($betweenDQuotes = true) : ($betweenSQuotes = true);
            } else if ($inValue && (($betweenDQuotes && ($char == "\"")) || ($betweenSQuotes && ($char == "'"))) && ($previousChar == "\\")) {
                // if inValue, a quote, betweenQuotes is true, and previous character is \ add to buffer
                $buffer   .= $char;
            } else if ($inValue && (($betweenDQuotes && ($char == "\"")) || ($betweenSQuotes && ($char == "'")))) {
                // if inValue, a quote, betweenQuotes is true set betweenQuotes to false, save to parameters array, empty buffer, set inValue false
                $buffer    = str_replace("\\\"","\"",$buffer);
                $buffer    = str_replace('\\\'','\'',$buffer);
                $parameters[trim($keyBuffer)] = trim($buffer);
                $buffer    = "";
                $inValue   = false;
                $betweenSQuotes = false;
                $betweenDQuotes = false;
            } else if ($inValue && !$betweenDQuotes && !$betweenSQuotes && ($char == ",")) {
                // if inValue, a comman, betweenQuotes is false, save to parameters array, empty buffer, set inValue false, set inKey true
                $parameters[trim($keyBuffer)] = trim($buffer);
                $buffer    = "";
                $inValue   = false;
                $inKey     = true;
            } else if ($inValue && (($i + 1) == $strLength)) {
                // if inValue and end of the string add to buffer, save to parameters array
                $buffer   .= $char;
                $parameters[trim($keyBuffer)] = trim($buffer);
            } else if ($inValue) {
                // if inValue add to buffer
                $buffer   .= $char;
            } else if (!$inValue && !$inKey && ($char == ",")) {
                // if inValue is false, inKey false, and a comma set inKey true
                $inKey = true;
            }
        }
        
        return $parameters;
        
    }
    
}
