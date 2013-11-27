<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2012 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
    private $extension = '.mustache';
    private $templates = array();
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
                return $this->templates[$name];
            } catch (Exception $e) {
                print "The partial, ".$name.", wasn't found so a pattern failed to build.\n";
            }
        } else {
           return $this->templates[$name];
        }
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
        //get pattern data 
        $pattern_data = $this->getPatternIncludeData($name);
        
        //get file path
        $fileName     = $this->getFileName($pattern_data['file']);

        //throw error if path is not found
        if (!file_exists($fileName)) {
            throw new Mustache_Exception_UnknownTemplateException($name);
        }

        //get file as string
        $file_str     = file_get_contents($fileName);
        
        //if pattern include was called with param, render param
        if (isset($pattern_data['param'])) {
            $file_str = $this->renderPattern($file_str, $pattern_data['param']);
        }
        
        return $file_str;
    }

    /**
     * Helper function for getting a Mustache template file name.
     *
     * @param string $name
     *
     * @return string Template file name
     */
    protected function getFileName($name)
    {
        $fileName = "";

        // test to see what kind of path was supplied
        $posDash  = strpos($name,"-");
        $posSlash = strpos($name,"/");
        if (($posSlash === false) && ($posDash !== false)) {
           
           list($patternType,$pattern) = $this->getPatternInfo($name);
           
           // see if the pattern is an exact match for patternPaths. if not iterate over patternPaths to find a likely match
           if (isset($this->patternPaths[$patternType][$pattern])) {
              $fileName = $this->baseDir."/".$this->patternPaths[$patternType][$pattern];
           } else if (isset($this->patternPaths[$patternType])) {
              foreach($this->patternPaths[$patternType] as $patternMatchKey=>$patternMatchValue) {
                  $pos = strpos($patternMatchKey,$pattern);
                  if ($pos !== false) {
                      $fileName = $this->baseDir."/".$patternMatchValue;
                      break;
                  }
              }
           }
        
        } else {
           $fileName = $this->baseDir."/".$name;
        }
        
        if (substr($fileName, 0 - strlen($this->extension)) !== $this->extension) {
            $fileName .= $this->extension;
        }
        
        return $fileName;
    }
    
    /**
     * Inspects pattern include call for param
     * ex:
     * {{ > patternType-pattern(param1: value, param2 ...) }}
     * 
     * and returns pattern data array with the following elements
     * 
     * string $file - the file name
     * 
     * array|null $param - an associative array of param ex: param_name => $param_value... or null if none found
     * 
     * @param string $pattern_include_call_str 
     * @return array returns pattern data array
     * 
     */
    
    protected function getPatternIncludeData($pattern_include_call_str)
    {
        //create an array to store pattern include data
        
        $pattern_data = array();
        
        // $pattern_include_call_str contains pattern name
        // followed by param, example:
        // patterntype-pattern (param1: value, param2: value2)
        
        $p_inc_str    = $pattern_include_call_str;
        
        //get param using regex
        //first remove all line breaks from string 
        
        $p_inc_str    = preg_replace("/[\r\n]*/","",$p_inc_str);
        
        //now use regex to get everything between parentheses ( )
        //note: this regex call ignores escaped parenteses \( and \)
        
        $matches      = preg_match('/\([^\(\\\\]*(?:\\\\.[^\)\\\\]*)*\)/s', $p_inc_str, $p_inc_param);
        
        //if no matches are found return file name only, and set
        //param to null
        
        if(!$matches){
            
            $pattern_data['file']  = $p_inc_str;
            $pattern_data['param'] = null;
            return $pattern_data;
        }
        
        //otherwise strip param from string and set file
        
        else {

            //strip param and add [file] as the include file name 
            $pattern_data['file']  = trim(preg_replace('/\([^\(\\\\]*(?:\\\\.[^\)\\\\]*)*\)/s', '', $p_inc_str));
        }
        
        //remove spaces & parentheses from either end of param string
        
        $p_inc_param  = trim($p_inc_param[0], '()');
        
        //split param by commas , but ignore escaped commas
        
        $p_inc_param = preg_split('/(?<!\\\),/', $p_inc_param);
        
        //unescape param values, remove back slashes from parenthesis and commas 
        
        $p_inc_param = str_replace(array("\(", "\)", "\,"), array("(", ")", ","), $p_inc_param);
        
        //loop through param and set $pattern_data[param] with values
        
        foreach($p_inc_param as $arg)
        {
            //separate args by the first colon :
            //ex param_name: param_value
            
            $r = explode(":", trim($arg), 2);
            
            //set pattern_data['param']['param_name'] with 
            //the un escaped values (remove slashes from commas and )
            $pattern_data['param'][$r[0]] = $r[1];
        }
        
        return $pattern_data;
    }
    
    /**
     * will replace each instance of {{ param_name }} with actual value 
     * 
     * @param string $pattern_string string to be parsed
     * @param array $param an array of parameters
     * @return string parsed $pattern_string 
     */
    
    function renderPattern($pattern_string, $param)
    {
        //loop through given param
        
        foreach($param as $k => $v) 
        { 
            //replace each instance of {{ param_name }} with actual value 
            
            $pattern_string = preg_replace('/{{([\s]*'.$k .'[\s]*)}}/', $v, $pattern_string);
        } 
        
        //return new pattern string
        
        return $pattern_string;
    }    

    private function getPatternInfo($name) {
	
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
	
}
