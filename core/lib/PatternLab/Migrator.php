<?php

/*!
 * Pattern Lab Migrator Class - v0.7.12
 *
 * Copyright (c) 2014 Dave Olsen http://dmolsen.com
 * Licensed under the MIT license
 *
 * Moves any necessary files from core/ into source/ or public/
 *
 */

namespace PatternLab;

class Migrator {
	
	/**
	* Make sure the config paths are set
	*/
	public function __construct() {
		// don't do anything
	}
	
	/**
	* Read through the migrations and move files as needed
	* @param  {Boolean}      is this a different version
	*/
	public function migrate($diffVersion = false) {
		
		$migrations      = new \DirectoryIterator(__DIR__."/../../migrations/");
		$migrationsValid = array();
		
		foreach ($migrations as $migration) {
			$filename = $migration->getFilename();
			if (!$migration->isDot() && $migration->isFile() && ($filename[0] != "_")) {
				$migrationsValid[] = $filename;
			}
		}
		
		asort($migrationsValid);
		
		foreach ($migrationsValid as $filename) {
			
			$basePath        = __DIR__."/../../../";
			$migrationData   = json_decode(file_get_contents(__DIR__."/../../migrations/".$filename));
			$checkType       = $migrationData->checkType;
			$sourcePath      = ($checkType == "fileExists") ? $basePath.$migrationData->sourcePath : $basePath.$migrationData->sourcePath.DIRECTORY_SEPARATOR;
			$destinationPath = ($checkType == "fileExists") ? $basePath.$migrationData->destinationPath : $basePath.$migrationData->destinationPath.DIRECTORY_SEPARATOR;
			
			if ($checkType == "dirEmpty") {
				
				$emptyDir = true;
				$objects  = new \DirectoryIterator($destinationPath);
				foreach ($objects as $object) {
					if (!$object->isDot() && ($object->getFilename() != "README") && ($object->getFilename() != ".DS_Store")) {
						$emptyDir = false;
					}
				}
				
				if ($emptyDir) {
					$this->runMigration($filename, $sourcePath, $destinationPath, false);
				}
				
			} else if ($checkType == "dirExists") {
				
				if (!is_dir($destinationPath)) {
					mkdir($destinationPath);
				}
				
			} else if ($checkType == "fileExists") {
				
				if (!file_exists($destinationPath)) {
					$this->runMigration($filename, $sourcePath, $destinationPath, true);
				}
				
			} else if (($checkType == "versionDiffDir") && $diffVersion) {
				
				// make sure the destination path exists
				if (!is_dir($destinationPath)) {
					mkdir($destinationPath);
				}
				
				$this->runMigration($filename, $sourcePath, $destinationPath, false);
				
			} else if (($checkType == "versionDiffFile") && $diffVersion) {
				
				$this->runMigration($filename, $sourcePath, $destinationPath, true);
				
			} else {
				
				print "Pattern Lab doesn't recognize a checkType of ".$checkType.". The migrator class is pretty thin at the moment.\n";
				exit;
				
			}
			
		}
		
	}
	
	/**
	* Run any migrations found in core/migrations that match the approved types
	* @param  {String}      the filename of the migration
	* @param  {String}      the path of the source directory
	* @param  {String}      the path to the destination
	* @param  {Boolean}     moving a single file or a directory
	*/
	protected function runMigration($filename, $sourcePath, $destinationPath, $singleFile) {
		
		$filename = str_replace(".json","",$filename);
		print "   Starting the ".$filename." migration...\n";
		
		if ($singleFile) {
			
			copy($sourcePath.$fileName,$destinationPath.$fileName);
			
		} else {
			
			$objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($sourcePath), \RecursiveIteratorIterator::SELF_FIRST);
			$objects->setFlags(\FilesystemIterator::SKIP_DOTS);
			
			foreach ($objects as $object) {
				
				// clean-up the file name and make sure it's not one of the pattern lab files or to be ignored
				$fileName = str_replace($sourcePath,"",$object->getPathname());
				
				// check to see if it's a new directory
				if ($object->isDir() && !is_dir($destinationPath.$fileName)) {	
					mkdir($destinationPath.$fileName);
				} else if ($object->isFile()) {
					copy($sourcePath.$fileName,$destinationPath.$fileName);
				}
				
			}
			
		}
		
		print "   Completed the ".$filename." migration...\n";
		
	}
	
}
