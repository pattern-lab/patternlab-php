<?php

/*!
 * Pattern Lab StartKit Class - v0.7.12
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Copy a starter kit from GitHub and put it into source/
 *
 */

namespace PatternLab;

class StarterKit {
	
	/**
	* Set-up a default var
	*/
	public function __construct($config) {
		$this->sourceDir     = __DIR__."/../../../".$config["publicDir"];
	}
	
	/**
	 * Fetch the starter kit from GitHub and put it into source/
	 * @param  {String}    path of the GitHub repo
	 *
	 * @return {String}    the modified file contents
	 */
	public function fetch($org,$repo,$tag) {
		
		//master
		//tag
		
		//get the path to the GH repo and validate it
		$tarballUrl = "https://github.com/".$org."/".$repo."/archive/".$tag.".tar.gzz";
		
		// try to download the given starter kit
		try {
			$starterKit = file_get_contents($tarballUrl);
		} catch (\Exception $e) {
			throw new \Exception('Something really gone wrong'.$e->getMessage(), 0, $e);
		}
		
		// write the starter kit to the temp directory
		$tempFile = tempnam(sys_get_temp_dir(), "pl-sk-archive.tar.gz");
		file_put_contents($tempFile, $starterKit);
		
		$zip = new ZipArchive;
		if ($zip->open('test.zip') === TRUE) {
		    $zip->extractTo('/my/destination/dir/');
		    $zip->close();
		    echo 'ok';
		} else {
		    echo 'failed';
		}
		
		unlink($tempFile);
		
		//see if source is empty, if not prompt if they want to delete stuff
		//delete everything
		//download
		//unzip
		//unpack into source
		
	}
	
}