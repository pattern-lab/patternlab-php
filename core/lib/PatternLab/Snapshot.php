<?php

/*!
 * Snapshot Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Copy public/ into a snapshot/v* directory
 *
 */

namespace PatternLab;

use \PatternLab\Config;
use \PatternLab\Snapshot\FilterIterator;
use \PatternLab\Template\Helper;

class Snapshot {
	
	/**
	* Set-up a default var
	*/
	public function __construct($config) {
		$this->publicDir     = __DIR__."/../../../".$config["publicDir"];
		$this->sourceDir     = "/../../../".$config["sourceDir"]."/_patterns".DIRECTORY_SEPARATOR;
		$this->snapshotsBase = $this->publicDir."/snapshots/";
	}
	
	/**
	* Get the arguments that have been passed to the script via the commmand line
	*/
	public function takeSnapshot($dir) {
		
		$snapshotsDir = "";
		
		// check to see if snapshots exists, if it doesn't make it
		if (!is_dir($this->snapshotsBase)) {
			mkdir($this->snapshotsBase);
		}
		
		// see if the dir passed through exists. if it does highlight an error.
		if ($dir) {
			
			// check to see if the given directory exists
			if (is_dir($this->snapshotsBase.$dir)) {
				print "The directory, ".$dir.", already exists. Please choose a new one for your snapshot.\n";
				exit;
			}
			
			// set-up the final snapshot directory
			$snapshotsDir = $this->snapshotsBase.$dir;
			
		} else {
			
			// get a list of dirs
			$scannedDirs = scandir($this->snapshotsBase);
			
			// remove the dot files
			$key = array_search('.', $scannedDirs);
			unset($scannedDirs[$key]);
			$key = array_search('..', $scannedDirs);
			unset($scannedDirs[$key]);
			
			// set-up the final snapshot directory
			$dirCount = 0;
			foreach ($scannedDirs as $scannedDir) {
				if (preg_match("/^v[0-9]{1,3}$/",$scannedDir)) {
					$dirCount++;
				}
			}
			
			$dirCount = $dirCount + 1;
			$snapshotsDir = $this->snapshotsBase."v".$dirCount;
			
		}
		
		// make the snapshot directory
		mkdir($snapshotsDir);
		
		// iterate over all of the other files in the source directory
		$directoryIterator = new \RecursiveDirectoryIterator($this->publicDir);
		$filteredIterator  = new SnapshotFilterIterator($directoryIterator);
		$objects           = new \RecursiveIteratorIterator($filteredIterator, \RecursiveIteratorIterator::SELF_FIRST);
		
		// make sure dots are skipped
		$objects->setFlags(\FilesystemIterator::SKIP_DOTS);
		
		foreach($objects as $name => $object) {
			
			// clean-up the file name and make sure it's not one of the pattern lab files or to be ignored
			$fileName = str_replace($this->publicDir.DIRECTORY_SEPARATOR,"",$name);
			
			// check to see if it's a new directory
			if ($object->isDir()) {
				mkdir($snapshotsDir."/".$fileName);
			}
			
			// check to see if it's a new file or a file that has changed
			if ($object->isFile()) {
				copy($this->publicDir."/".$fileName,$snapshotsDir."/".$fileName);
			}
			
		}
		
		// re-scan to get the latest addition
		$html = "";
		$scannedDirs = scandir($this->snapshotsBase);
		
		// remove the dot files
		$key = array_search('.', $scannedDirs);
		unset($scannedDirs[$key]);
		$key = array_search('..', $scannedDirs);
		unset($scannedDirs[$key]);
		$key = array_search('index.html', $scannedDirs);
		unset($scannedDirs[$key]);
		
		usort($scannedDirs, "strnatcmp");
		
		foreach ($scannedDirs as $scanDir) {
			$html .= "<li> <a href=\"".$scanDir."/\" target=\"_parent\">".$scanDir."</a></li>\n";
		}
		
		$d = array("html" => $html);
		
		$templateLoader = new TemplateLoader();
		$templateHelper = new TemplateHelper($this->sourceDir);
		
		// render the viewall template
		$v = $templateLoader->vanilla();
		$h = $v->render($templateHelper->mainPageHead);
		$f = $v->render($templateHelper->mainPageFoot);
		
		// render the snapshot page
		$m = $templateLoader->fileSystem();
		$r = $m->render('snapshot', $d);
		$r = $h.$r.$f;
		
		file_put_contents($this->snapshotsBase."index.html",$r);
		
		print "finished taking a snapshot...\n";
		
	}
	
}