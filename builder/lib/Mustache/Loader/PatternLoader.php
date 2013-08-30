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
        $fileName = $this->getFileName($name);

        if (!file_exists($fileName)) {
            throw new Mustache_Exception_UnknownTemplateException($name);
        }

        return file_get_contents($fileName);
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
